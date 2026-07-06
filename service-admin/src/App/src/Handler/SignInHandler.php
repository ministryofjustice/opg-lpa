<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Sign-in is handled by the ALB authenticate-oidc action before requests reach PHP.
 * This handler exists only to redirect already-authenticated users to the home page.
 */
class SignInHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->redirectToRoute('home');
    }
}
