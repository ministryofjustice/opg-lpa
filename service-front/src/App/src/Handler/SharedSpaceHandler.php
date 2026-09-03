<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SharedSpace\SharedSpaceForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SharedSpaceHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly SharedSpaceService $sharedSpaceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $identity */
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        /** @var SharedSpaceForm $form */
        $form = $this->formElementManager->get(SharedSpaceForm::class);
        $authError = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $problem = $this->sharedSpaceService->import(
                    $form->get('email')->getValue(),
                    $form->get('password')->getValue(),
                );

                if ($problem === 'user-already-in-space') {
                    return new RedirectResponse('/shared-space/import-failed');
                } elseif (is_string($problem)) {
                    $authError = match ($problem) {
                        'account-not-active' => 'not-activated',
                        'account-locked/max-login-attempts',
                        'invalid-user-credentials/account-locked' => 'locked',
                        default => 'authentication-failed',
                    };
                } else {
                    return new RedirectResponse('/shared-space?import=success');
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
                    'form' => $form,
                    'authError' => $authError,
                    'members' => $members,
                    'invites' => $invites,
                    'sharedSpaceName' => $sharedSpaceName,
                    'signedInUserIsAdmin' => $isAdmin,
                    'inviteSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'sent',
                    'revokeSuccess' => ($request->getQueryParams()['invite'] ?? null) === 'revoked',
                    'memberDeleted' => ($request->getQueryParams()['member'] ?? null) === 'deleted',
                    'importSuccess' => ($request->getQueryParams()['import'] ?? null) === 'success',
                ],
            ),
        ));
    }
}
