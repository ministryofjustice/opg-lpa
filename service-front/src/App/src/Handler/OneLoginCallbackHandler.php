<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OneLoginCallbackHandler implements RequestHandlerInterface
{
    private const string SESSION_KEY_ONELOGIN     = 'onelogin_auth';
    private const string SESSION_KEY_IDENTITY     = 'identity';
    private const string SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';
    private const string ERROR_TEMPLATE           = 'application/general/auth/onelogin-error.twig';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly OneLoginService $oneLoginService,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['error'])) {
            $this->logger->warning('auth.onelogin.provider_error', [
                'error'             => $queryParams['error'],
                'error_description' => $queryParams['error_description'] ?? null,
            ]);

            return $this->renderError('There was a problem signing you in. Please try again.');
        }

        $code  = $queryParams['code']  ?? null;
        $state = $queryParams['state'] ?? null;

        if (!is_string($code) || $code === '' || !is_string($state) || $state === '') {
            return $this->renderError('The sign-in request was incomplete. Please try again.');
        }

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        /** @var mixed $oneLoginAuth */
        $oneLoginAuth = $session->get(self::SESSION_KEY_ONELOGIN);

        $expectedState = is_array($oneLoginAuth) ? ($oneLoginAuth['state'] ?? null) : null;
        $expectedNonce = is_array($oneLoginAuth) ? ($oneLoginAuth['nonce'] ?? null) : null;
        $redirectUri   = is_array($oneLoginAuth) ? ($oneLoginAuth['redirect_uri'] ?? null) : null;

        if (
            !is_string($expectedState) || $expectedState === ''
            || !is_string($expectedNonce) || $expectedNonce === ''
            || !is_string($redirectUri) || $redirectUri === ''
        ) {
            $this->logger->warning('auth.onelogin.session_missing', [
                'has_code'  => true,
                'has_state' => true,
            ]);

            return $this->renderError('Your sign-in session has expired. Please try again.');
        }

        try {
            if (!hash_equals($expectedState, $state)) {
                $this->logger->warning('auth.onelogin.state_mismatch', [
                    'expected_prefix' => substr($expectedState, 0, 8),
                ]);

                return $this->renderError('The sign-in request could not be verified. Please try again.');
            }

            $preAuthUrl = $session->get(self::SESSION_KEY_PRE_AUTH_URL);

            try {
                $result = $this->oneLoginService->callback(
                    $code,
                    $state,
                    $expectedNonce,
                    $redirectUri,
                );
            } catch (RuntimeException $e) {
                $this->logger->error('auth.onelogin.callback_failed', ['message' => $e->getMessage()]);

                return $this->renderError('There was a problem completing your sign-in. Please try again.');
            }

            // Regenerate to prevent session fixation before writing any identity data.
            $session->regenerate();

            if ($result['linked']) {
                // Account already linked: establish full authenticated session.
                $session->clear();
                $session->set(self::SESSION_KEY_IDENTITY, $result['identity']);

                if (is_string($preAuthUrl) && $preAuthUrl !== '') {
                    return new RedirectResponse($preAuthUrl);
                }

                return new RedirectResponse('/user/dashboard');
            }

            $session->clear();

            if (is_string($preAuthUrl) && $preAuthUrl !== '') {
                $session->set(self::SESSION_KEY_PRE_AUTH_URL, $preAuthUrl);
            }

            $this->sessionManager->setPendingLink($session, $result['sub'], $result['email']);

            return new RedirectResponse('/link-or-create-account');
        } finally {
            if ($session->has(self::SESSION_KEY_ONELOGIN)) {
                $session->unset(self::SESSION_KEY_ONELOGIN);
            }
        }
    }

    private function renderError(string $message): HtmlResponse
    {
        return new HtmlResponse(
            $this->renderer->render(self::ERROR_TEMPLATE, ['error' => $message]),
            400,
        );
    }
}
