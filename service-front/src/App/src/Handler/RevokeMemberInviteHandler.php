<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RevokeMemberInviteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly FormElementManager $formElementManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $email = $request->getQueryParams()['email'] ?? '';

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            /** @var RouteResult|null $routeResult */
            $routeResult = $request->getAttribute(RouteResult::class);
            $inviteId = $routeResult ? $routeResult->getMatchedParams()['invite-id'] ?? null : null;

            $ok = $this->sharedSpaceService->revokeInvite($inviteId);

            if ($ok) {
                return new RedirectResponse('/shared-space/manage?invite=revoked');
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/revoke-invite.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'email' => $email,
                ],
            ),
        ));
    }
}
