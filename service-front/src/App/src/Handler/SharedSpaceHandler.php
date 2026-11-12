<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\DataModel\User\User as SharedUser;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

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
        $error = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();

            if (is_array($postData) && isset($postData['resend'])) {
                try {
                    /** @var SharedUser|null $userDetails */
                    $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

                    $invitedName = $this->sharedSpaceService->resendInvite($userDetails, $postData['resend']);

                    if ($invitedName !== null) {
                        return new RedirectResponse('/shared-space?invite=sent&invitedMemberName=' . urlencode($invitedName));
                    }
                } catch (Throwable $e) {
                    $error = match ($e->getMessage()) {
                        'user-already-in-shared-space' => 'Email address belongs to an active member of this shared space -– enter a different email address',
                        'invite-already-exists' => 'Email address is for a pending member of this shared space – enter a different one or cancel the previous invite',
                        default => 'An error occurred while sending the invitation. Please try again later.',
                    };
                }
            }
        }

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
        $sharedSpaceName = $result['name'] ?? '';
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
                    'error' => $error,
                    'members' => $members,
                    'invites' => $invites,
                    'sharedSpaceName' => $sharedSpaceName,
                    'signedInUserIsAdmin' => $isAdmin,
                    'invitedMemberName'    => $request->getQueryParams()['invitedMemberName'] ?? null,
                    'inviteSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'sent',
                    'revokeSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'revoked',
                    'memberDeleted' => ($request->getQueryParams()['member'] ?? null) === 'deleted',
                ],
            ),
        ));
    }
}
