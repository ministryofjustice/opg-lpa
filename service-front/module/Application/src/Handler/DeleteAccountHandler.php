<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteAccountHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity === null) {
            return new RedirectResponse('/login');
        }

        $html = $this->renderer->render(
            'application/authenticated/delete/index.twig',
            $this->getTemplateVariables($request)
        );

        return new HtmlResponse($html);
    }
}
