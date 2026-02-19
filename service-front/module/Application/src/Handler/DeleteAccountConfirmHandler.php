<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteAccountConfirmHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity === null) {
            return new RedirectResponse('/login');
        }

        // Delete all v2 LPAs, their v2 Personal details, and their Auth account.
        if (!$this->userService->delete()) {
            $html = $this->renderer->render(
                'error/500.twig',
                $this->getTemplateVariables($request)
            );

            return new HtmlResponse($html, 500);
        }

        return new RedirectResponse('/deleted');
    }
}
