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
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $inviteId = (string) ($routeResult?->getMatchedParams()['invite-id'] ?? '');

        $invites = $this->sharedSpaceService->getMembersAndInvites()['invites'] ?? [];
        $invite = array_find($invites, fn (array $invite) => (string) $invite['id'] === $inviteId);

        if ($invite === null) {
            return new RedirectResponse('/shared-space');
        }

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            if ($this->sharedSpaceService->revokeInvite($inviteId)) {
                return new RedirectResponse(
                    '/shared-space?invite=revoked&invitedMemberName=' . urlencode($invite['fullName'])
                );
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/revoke-invite.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'inviteeName' => $invite['fullName'],
                    'inviteeEmail' => $invite['email'],
                ],
            ),
        ));
    }
}
