<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CannotLinkAccountHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        if ($this->sessionManager->getPendingLink($session) === null) {
            $this->logger->warning('auth.onelogin.cannot_link_missing_pending_link');

            return new RedirectResponse('/login');
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/cannot-link-account.twig',
        ));
    }
}
