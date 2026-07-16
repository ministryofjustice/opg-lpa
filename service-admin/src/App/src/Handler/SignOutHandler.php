<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignOutHandler extends AbstractHandler
{
    // The ALB shards its own auth session cookie across multiple numbered cookies
    // once the encrypted claims exceed ~4KB (see AWS docs on authenticate-oidc).
    // We don't know in advance how many shards a given session used, so we send
    // expiry headers for a generous range of indexes; clearing a cookie that was
    // never set is a no-op for the browser.
    private const int ALB_SESSION_COOKIE_SHARD_COUNT = 10;

    public function __construct(
        private readonly ?string $cognitoLogoutUrl,
        #[\SensitiveParameter]
        private readonly ?string $albCookiePrefix,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->auditLog(
            $request->getAttribute(RequestAttributes::USER_EMAIL),
            'admin.auth.sign_out',
            'Admin signed out',
        );

        $session = $request->getAttribute(SessionInterface::class);
        $session->clear();
        $session->set('signed_out', true);

        // In production the ALB caches the authenticated session in its own cookie
        // (AWSELBAuthSessionCookie), separate from both the PHP session and Cognito's
        // hosted-UI session. Redirecting to Cognito's logout endpoint only clears the
        // Cognito session — the ALB will keep re-authenticating from its cached cookie
        // until it expires (session_timeout), so we must also expire it here.
        if ($this->cognitoLogoutUrl !== null) {
            $response = new RedirectResponse($this->cognitoLogoutUrl);

            if ($this->albCookiePrefix !== null) {
                foreach (range(0, self::ALB_SESSION_COOKIE_SHARD_COUNT - 1) as $shard) {
                    $response = FigResponseCookies::set(
                        $response,
                        SetCookie::create(sprintf('%s-%d', $this->albCookiePrefix, $shard))
                            ->withPath('/')
                            ->withSecure()
                            ->withHttpOnly()
                            ->expire(),
                    );
                }
            }

            return $response;
        }

        // Local dev: the signed_out session flag tells AlbSimulatorMiddleware to stop
        // injecting a token. The user will be redirected to /sign-in by AuthorizationMiddleware
        // and can sign back in from there.
        return $this->redirectToRoute('sign.in');
    }
}
