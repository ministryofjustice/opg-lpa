<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SharedSpace\InviteMemberForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
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
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        /** @var InviteMemberForm $form */
        $form = $this->formElementManager->get(InviteMemberForm::class);

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

                $ok = $this->sharedSpaceService->invite(
                    $userDetails->getEmail()->getAddress(),
                    $form->get('firstNames')->getValue(),
                    $form->get('lastName')->getValue(),
                    $form->get('email')->getValue(),
                    $permissions->isChecked(),
                );

                if ($ok) {
                    return new RedirectResponse('/shared-space/manage?invite=sent');
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/invite.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'csrfToken' => $csrfToken,
                ],
            ),
        ));
    }
}
