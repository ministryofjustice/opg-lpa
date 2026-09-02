<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Receives GOV.UK One Login's back-channel logout notification.
 */
class OneLoginBackChannelLogoutHandler implements RequestHandlerInterface
{
    private const string LOGOUT_TOKEN_PARAM = 'logout_token';

    public function __construct(
        private readonly OneLoginService $oneLoginService,
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

            return $this->response(400);
        }

        try {
            $accepted = $this->oneLoginService->backChannelLogout($logoutToken);
        } catch (Throwable $e) {
            $this->logger->error('auth.onelogin.backchannel_logout_failed', [
                'message' => $e->getMessage(),
            ]);

            return $this->response(502);
        }

        if (!$accepted) {
            $this->logger->warning('auth.onelogin.backchannel_logout_rejected', [
                'reason' => 'not_accepted',
            ]);

            return $this->response(400);
        }

        return $this->response(200);
    }

    private function response(int $status): ResponseInterface
    {
        return new EmptyResponse($status, ['Cache-Control' => 'no-store']);
    }
}
