<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeletedAccountHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->clearSession();

        $html = $this->renderer->render('application/general/auth/deleted.twig');

        return new HtmlResponse($html);
    }

    private function clearSession(): void
    {
        $this->authenticationService->clearIdentity();

        $this->sessionManagerSupport->getSessionManager()->destroy([
            'clear_storage' => true
        ]);
    }
}
