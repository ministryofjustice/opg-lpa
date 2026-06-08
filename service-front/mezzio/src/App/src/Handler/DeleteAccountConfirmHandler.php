<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Authentication\AuthenticationService;
use App\Service\UserDetails;
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
        private readonly UserDetails $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
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
