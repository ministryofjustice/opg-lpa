<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SharedSpaceHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $identity */
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        $result = $this->sharedSpaceService->getMembersAndInvites();
        if ($result === null) {
            return new HtmlResponse($this->renderer->render(
                'application/authenticated/shared-space/about.twig',
                $this->getTemplateVariables($request),
            ));
        }

        /** @var array<int, SharedSpaceMember> $members */
        $members = $result['members'] ?? [];
        $invites = $result['invites'] ?? [];
        $isAdmin = false;

        foreach ($members as $member) {
            if ($member->getUserId() === $identity->id()) {
                $isAdmin = $member->isAdmin();
                break;
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/manage.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'members' => $members,
                    'invites' => $invites,
                    'signedInUserIsAdmin' => $isAdmin,
                    'inviteSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'sent',
                    'revokeSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'revoked',
                    'memberDeleted' => ($request->getQueryParams()['member'] ?? null) === 'deleted',
                ],
            ),
        ));
    }
}
