<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignOutHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->auditLog(
            $request->getAttribute(RequestAttributes::USER_EMAIL),
            'admin.auth.sign_out',
            'Admin signed out',
        );

        $request->getAttribute(SessionInterface::class)->clear();

        return $this->redirectToRoute('sign.in');
    }
}
