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
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class SharedSpaceHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    /**
     * Flash key for the outcome of an invite action, shaped ['outcome' => string, 'name' => string].
     * Flashed rather than put in the redirect URL so the invitee's name doesn't end up in logs or history.
     */
    public const string INVITE_OUTCOME_FLASH = 'shared_space_invite_outcome';

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

                    if ($invitedName === null) {
                        $error = 'An error occurred while sending the invitation. Please try again later.';
                    } else {
                        /** @var FlashMessagesInterface $flash */
                        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                        $flash->flash(self::INVITE_OUTCOME_FLASH, ['outcome' => 'resent', 'name' => $invitedName]);

                        return new RedirectResponse('/shared-space');
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

        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        $inviteFlash = $flash instanceof FlashMessagesInterface ? $flash->getFlash(self::INVITE_OUTCOME_FLASH) : null;
        $inviteOutcome = is_array($inviteFlash) ? ($inviteFlash['outcome'] ?? null) : null;

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
                    'invitedMemberName' => is_array($inviteFlash) ? ($inviteFlash['name'] ?? null) : null,
                    'updatedMemberName'    => $request->getQueryParams()['updatedMemberName'] ?? null,
                    'inviteSuccess' => $inviteOutcome === 'sent',
                    'revokeSuccess' => $inviteOutcome === 'revoked',
                    'resendSuccess' => $inviteOutcome === 'resent',
                    'memberDeleted' => ($request->getQueryParams()['member'] ?? null) === 'deleted',
                    'memberUpdated' => ($request->getQueryParams()['member'] ?? null) === 'updated',
                ],
            ),
        ));
    }
}
