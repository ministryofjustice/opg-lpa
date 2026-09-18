<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Authentication\AuthenticationService;
use App\Service\SharedSpace\SharedSpaceService;
use App\Service\UserDetails as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
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
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            if (!$this->userService->delete()) {
                $html = $this->renderer->render(
                    'error/500.twig',
                    $this->getTemplateVariables($request)
                );

                return new HtmlResponse($html, StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            }

            return new RedirectResponse('/deleted');
        }

        $memberCount = $this->sharedSpaceService->getMemberCount();

        $html = $this->renderer->render(
            'application/authenticated/delete/index.twig',
            array_merge(['memberCount' => $memberCount], $this->getTemplateVariables($request))
        );

        return new HtmlResponse($html);
    }
}
