<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OneLoginHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_IDENTITY = 'identity';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        // If already authenticated, redirect to dashboard
        if ($session->has(self::SESSION_KEY_IDENTITY)) {
            return new RedirectResponse('/user/dashboard');
        }

        return new HtmlResponse(
            $this->renderer->render('application/general/auth/onelogin.twig')
        );
    }
}
