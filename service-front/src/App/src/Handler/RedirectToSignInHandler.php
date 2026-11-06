<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\SignInPage;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stands in for the password sign-in pages once GOV.UK One Login is the only way to sign in.
 */
class RedirectToSignInHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new RedirectResponse(SignInPage::path());
    }
}
