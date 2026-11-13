<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\RequestAttribute;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\User\User;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResendMemberInviteHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $inviteId = (string) ($routeResult?->getMatchedParams()['invite-id'] ?? '');

        /** @var User $inviter */
        $inviter = $request->getAttribute(RequestAttribute::USER_DETAILS);

        $inviteeName = $this->sharedSpaceService->resendInvite($inviter, $inviteId);

        if ($inviteeName === null) {
            return new RedirectResponse('/shared-space?invite=resend-failed');
        }

        return new RedirectResponse('/shared-space?invite=resent&invitedMemberName=' . urlencode($inviteeName));
    }
}
