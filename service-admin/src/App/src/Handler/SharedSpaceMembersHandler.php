<?php

declare(strict_types=1);

namespace App\Handler;

use App\RequestAttributes;
use App\Service\Paginator;
use App\Service\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress UnusedClass
 */
class SharedSpaceMembersHandler extends AbstractHandler
{
    public function __construct(private readonly SharedSpaceService $sharedSpaceService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $sharedSpaceId = $request->getAttribute('sharedSpaceId');
        $queryParams = $request->getQueryParams();

        $membersPaginator = new Paginator();
        $membersPaginator->setPerPage(20);
        $membersPaginator->setPage((int) ($queryParams['membersPage'] ?? 1));

        $invitesPaginator = new Paginator();
        $invitesPaginator->setPerPage(20);
        $invitesPaginator->setPage((int) ($queryParams['invitesPage'] ?? 1));

        $result = $this->sharedSpaceService->members(
            $sharedSpaceId,
            $membersPaginator->getPage(),
            $membersPaginator->getPerPage(),
            $invitesPaginator->getPage(),
            $invitesPaginator->getPerPage(),
        );

        if ($result === false) {
            return new HtmlResponse($this->getTemplateRenderer()->render('app::shared-space-members', [
                'sharedSpaceId' => $sharedSpaceId,
                'failureReason' => 'No shared space found',
            ]), 404);
        }

        $membersPaginator->setTotal($result['membersTotal']);
        $invitesPaginator->setTotal($result['invitesTotal']);

        $this->auditLog(
            $request->getAttribute(RequestAttributes::USER_EMAIL),
            'admin.shared-space.members.view',
            'Admin viewed shared space members',
            [
                'shared_space_id' => $sharedSpaceId,
                'member_count' => count($result['members']),
                'invite_count' => count($result['invites']),
            ],
        );

        return new HtmlResponse($this->getTemplateRenderer()->render('app::shared-space-members', [
            'sharedSpaceId' => $sharedSpaceId,
            'sharedSpaceName' => $result['sharedSpaceName'],
            'members' => $result['members'],
            'membersPaginator' => $membersPaginator,
            'invites' => $result['invites'],
            'invitesPaginator' => $invitesPaginator,
        ]));
    }
}
