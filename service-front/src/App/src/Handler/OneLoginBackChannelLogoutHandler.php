<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\LogoutTokenReplayGuard;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\SignedOutSubjects;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Receives GOV.UK One Login's back-channel logout notification.
 *
 * One Login confirmed they expect 200 even for a token we reject (LPAL-2583), so rejections are
 * logged rather than answered with 400. Our own failure (the API unreachable, or unable to fetch
 * One Login's signing keys) returns 5xx so One Login's retry can try again; if the API had already
 * processed the token, the retry is refused as a replay.
 */
class OneLoginBackChannelLogoutHandler implements RequestHandlerInterface
{
    private const string LOGOUT_TOKEN_PARAM = 'logout_token';

    public function __construct(
        private readonly OneLoginService $oneLoginService,
        private readonly SignedOutSubjects $signedOutSubjects,
        private readonly LogoutTokenReplayGuard $replayGuard,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        $logoutToken = is_array($body) ? ($body[self::LOGOUT_TOKEN_PARAM] ?? null) : null;

        if (!is_string($logoutToken) || trim($logoutToken) === '') {
            $this->logger->info('auth.onelogin.backchannel_logout_rejected', [
                'reason' => 'missing_logout_token',
            ]);

            return $this->response(200);
        }

        try {
            if (!$this->replayGuard->claim($logoutToken)) {
                $this->logger->warning('auth.onelogin.backchannel_logout_rejected', [
                    'reason' => 'replayed_jti',
                ]);

                return $this->response(400);
            }
        } catch (Throwable $e) {
            $this->logger->error('auth.onelogin.backchannel_logout_failed', [
                'message' => $e->getMessage(),
            ]);

            return $this->response(502);
        }

        try {
            $signOut = $this->oneLoginService->backChannelLogout($logoutToken);
        } catch (Throwable $e) {
            $this->releaseClaim($logoutToken);

            $this->logger->error('auth.onelogin.backchannel_logout_failed', [
                'message' => $e->getMessage(),
            ]);

            return $this->response(502);
        }

        if ($signOut === null) {
            $this->releaseClaim($logoutToken);

            $this->logger->warning('auth.onelogin.backchannel_logout_rejected', [
                'reason' => 'not_accepted',
            ]);

            return $this->response(200);
        }

        $this->rememberSignOut($signOut['sub'], $signOut['signedOutAt']);

        return $this->response(200);
    }

    /**
     * Stops a user who has no Make account yet from finishing a pending account link.
     * The logout itself has already succeeded, so a failure here does not change the response.
     */
    private function rememberSignOut(string $sub, int $signedOutAt): void
    {
        try {
            $this->signedOutSubjects->recordSignOut($sub, $signedOutAt);
        } catch (Throwable $e) {
            $this->logger->error('auth.onelogin.signed_out_record_failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A token that was not accepted must not stay claimed: a forged token would otherwise block
     * the genuine one, and One Login's retry after our failure would look like a replay.
     */
    private function releaseClaim(#[\SensitiveParameter] string $logoutToken): void
    {
        try {
            $this->replayGuard->release($logoutToken);
        } catch (Throwable $e) {
            $this->logger->error('auth.onelogin.backchannel_logout_release_failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function response(int $status): ResponseInterface
    {
        return new EmptyResponse($status, ['Cache-Control' => 'no-store']);
    }
}
