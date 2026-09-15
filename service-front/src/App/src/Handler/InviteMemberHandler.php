<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SharedSpace\InviteMemberForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MakeShared\DataModel\User\User;
use Throwable;

class InviteMemberHandler implements RequestHandlerInterface
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
        /** @var InviteMemberForm $form */
        $form = $this->formElementManager->get(InviteMemberForm::class);
        $error = null;

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            /** @var User|null $userDetails */
            $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

            if ($form->isValid()) {
                /** @var Checkbox */
                $permissions = $form->get('permissions');

                try {
                    $ok = $this->sharedSpaceService->invite(
                        $userDetails->getEmail()->getAddress(),
                        $form->get('firstNames')->getValue(),
                        $form->get('lastName')->getValue(),
                        $form->get('email')->getValue(),
                        $permissions->isChecked(),
                    );

                    if ($ok) {
                        return new RedirectResponse('/shared-space?invite=sent');
                    }
                } catch (Throwable $e) {
                    match ($e->getMessage()) {
                        'user-already-in-shared-space' => $form->setMessages(['email' => ['This email address is already part of the shared space']]),
                        'invite-already-exists' => $form->setMessages(['email' => ['This email address has already been invited to the shared space']]),
                        default => $error = 'An error occurred while sending the invitation. Please try again later.',
                    };
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/invite.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'error' => $error,
                ],
            ),
        ));
    }
}
