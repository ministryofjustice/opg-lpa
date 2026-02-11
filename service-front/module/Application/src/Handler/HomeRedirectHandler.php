<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HomeRedirectHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $redirectUrl = $this->config['redirects']['index'] ?? '/';

        return new RedirectResponse($redirectUrl);
    }
}
