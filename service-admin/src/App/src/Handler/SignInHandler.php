<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Sign-in is handled by the ALB authenticate-oidc action before requests reach PHP.
 *
 * In production, a user reaching this handler is already authenticated (ALB
 * guarantees it). Redirect them to home.
 *
 * Locally, after sign-out the signed_out session flag means AlbSimulatorMiddleware
 * skips token injection. The user is redirected here unauthenticated. We redirect
 * them to /sign-in?signin=1, which is the explicit trigger AlbSimulatorMiddleware
 * uses to clear the flag and inject a fresh token before this handler runs again.
 */
class SignInHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userEmail = $request->getAttribute(RequestAttributes::USER_EMAIL);

        if ($userEmail !== null) {
            return $this->redirectToRoute('home');
        }

        // Not authenticated (local signed-out state). Render the sign-in page so the
        // user explicitly clicks to re-authenticate via ?signin=1.
        return new HtmlResponse($this->getTemplateRenderer()->render('app::sign-in', [
            'signinUrl' => $this->getUrlHelper()->generate('sign.in', [], ['signin' => '1']),
        ]));
    }
}
