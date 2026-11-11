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
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
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
            $blockingSharedSpaceName = $this->sharedSpaceNeedingThisUserAsAdmin($request);
            if ($blockingSharedSpaceName !== null) {
                return new HtmlResponse($this->renderer->render(
                    'application/authenticated/delete/cannot-delete.twig',
                    array_merge(
                        ['sharedSpaceName' => $blockingSharedSpaceName],
                        $this->getTemplateVariables($request)
                    )
                ));
            }

            if (!$this->userService->delete()) {
                $html = $this->renderer->render(
                    'error/500.twig',
                    $this->getTemplateVariables($request)
                );

                return new HtmlResponse($html, StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            }

            return new RedirectResponse('/deleted');
        }

        $memberCount = $this->sharedSpacesEnabled ? $this->sharedSpaceService->getMemberCount() : null;

        $sharedSpaceName = null;
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        if ($this->oneLoginEnabled && $memberCount > 1 && $identity instanceof User) {
            $sharedSpaceName = $this->sharedSpaceService->getMember($identity->id())?->getSharedSpaceName();
        }

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

    private function sharedSpaceNeedingThisUserAsAdmin(ServerRequestInterface $request): ?string
    {
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        if (!$this->sharedSpacesEnabled || !$identity instanceof User || !$identity->inSharedSpace()) {
            return null;
        }

        $sharedSpace = $this->sharedSpaceService->getMembersAndInvites();
        if ($sharedSpace === null) {
            return null;
        }

        $otherMembers = array_filter(
            $sharedSpace['members'],
            fn (SharedSpaceMember $member) => $member->getUserId() !== $identity->id(),
        );

        $otherAdmins = array_filter($otherMembers, fn (SharedSpaceMember $member) => $member->isAdmin());

        return $otherMembers !== [] && $otherAdmins === [] ? $sharedSpace['name'] : null;
    }
}
