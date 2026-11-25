<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\SharedSpace\SharedSpaceService;
use App\View\Twig\FlashMessenger;
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

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        /** @var ?SharedUser $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);
        $error = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();

            if (is_array($postData) && isset($postData['resend'])) {
                try {
                    $invitedName = $this->sharedSpaceService->resendInvite($userDetails, $postData['resend']);

                    if ($invitedName === null) {
                        $error = 'An error occurred while sending the invitation. Please try again later.';
                    } else {
                        /** @var FlashMessagesInterface $flash */
                        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                        $flash->flash(FlashMessenger::SUCCESS, ['The invite has been resent to ' . $invitedName . '.']);

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

        if ($userDetails->getSharedSpaceId() === null) {
            return new HtmlResponse($this->renderer->render(
                'application/authenticated/shared-space/about.twig',
                $this->getTemplateVariables($request),
            ));
        }

        $result = $this->sharedSpaceService->getMembersAndInvites();

        /** @var array<int, SharedSpaceMember> $members */
        $members = $result['members'] ?? [];
        $invites = $result['invites'] ?? [];
        $sharedSpaceName = $result['name'] ?? '';
        $isAdmin = false;

        foreach ($members as $member) {
            if ($member->getUserId() === $userDetails->getId()) {
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
                    'updatedMemberName'    => $request->getQueryParams()['updatedMemberName'] ?? null,
                    'memberUpdated' => ($request->getQueryParams()['member'] ?? null) === 'updated',
                ],
            ),
        ));
    }
}
