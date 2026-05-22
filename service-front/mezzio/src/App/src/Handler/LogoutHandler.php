<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($session instanceof SessionInterface) {
            $session->clear();
            $session->regenerate();
        }

        $logoutUrl = $this->config['redirects']['logout'] ?? '/';

        return new RedirectResponse($logoutUrl);
    }
}
