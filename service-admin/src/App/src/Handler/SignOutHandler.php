<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignOutHandler extends AbstractHandler
{
    public function __construct(private readonly ?string $cognitoLogoutUrl)
    {
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

        // In production the ALB sets its own auth cookie (AWSELBAuthSessionCookie).
        // Clearing the PHP session alone doesn't sign the user out of Cognito — they'd
        // be immediately re-authenticated on the next request. Redirect to Cognito's
        // hosted UI logout endpoint to clear both the Cognito session and ALB cookie.
        if ($this->cognitoLogoutUrl !== null) {
            return new RedirectResponse($this->cognitoLogoutUrl);
        }

        // Local dev: the signed_out session flag tells AlbSimulatorMiddleware to stop
        // injecting a token. The user will be redirected to /sign-in by AuthorizationMiddleware
        // and can sign back in from there.
        return $this->redirectToRoute('sign.in');
    }
}
