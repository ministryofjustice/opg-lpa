<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Authentication\AuthenticationService;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
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
        private readonly bool $sharedSpacesEnabled = false,
        private readonly bool $oneLoginEnabled = false,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $result = $this->userService->delete();

            if ($result === 'no-other-admin') {
                return new HtmlResponse($this->renderer->render(
                    'application/authenticated/delete/cannot-delete.twig',
                    array_merge(
                        ['sharedSpaceName' => $this->sharedSpaceName($request)],
                        $this->getTemplateVariables($request)
                    )
                ));
            }

            if ($result !== true) {
                $html = $this->renderer->render(
                    'error/500.twig',
                    $this->getTemplateVariables($request)
                );

                return new HtmlResponse($html, StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            }

            return new RedirectResponse('/deleted');
        }

        $memberCount = $this->sharedSpacesEnabled ? $this->sharedSpaceService->getMemberCount() : null;

        $sharedSpaceName = $this->oneLoginEnabled && $memberCount > 1 ? $this->sharedSpaceName($request) : null;

        $html = $this->renderer->render(
            'application/authenticated/delete/index.twig',
            array_merge(
                [
                    'memberCount' => $memberCount,
                    'oneLoginEnabled' => $this->oneLoginEnabled,
                    'sharedSpaceName' => $sharedSpaceName,
                ],
                $this->getTemplateVariables($request)
            )
        );

        return new HtmlResponse($html);
    }

    private function sharedSpaceName(ServerRequestInterface $request): ?string
    {
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        return $identity instanceof User
            ? $this->sharedSpaceService->getMember($identity->id())?->getSharedSpaceName()
            : null;
    }
}
