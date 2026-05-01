<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignOutHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->auditLog(
            $request,
            'admin.auth.sign_out',
            'Admin signed out',
        );

        $this->clearTokenData();

        return $this->redirectToRoute('sign.in');
    }
}
