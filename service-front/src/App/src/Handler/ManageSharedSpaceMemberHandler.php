<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SharedSpace\SharedSpaceMemberForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ManageSharedSpaceMemberHandler implements RequestHandlerInterface
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
        $routeResult = $request->getAttribute(RouteResult::class);
        $memberId = $routeResult?->getMatchedParams()['member-id'] ?? null;

        // The API only returns member details to admins of the shared space,
        // so a null response here covers both "member not found" and
        // "signed-in user is not an admin" - either way, redirect back.
        $member = $this->sharedSpaceService->getMember($memberId);
        if ($member === null) {
            return new RedirectResponse('/shared-space');
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get(SharedSpaceMemberForm::class);

        $error = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                /** @var Checkbox $permissions */
                $permissions = $form->get('permissions');
                /** @var Radio $status */
                $status = $form->get('status');

                if ($this->sharedSpaceService->updateMember($memberId, $permissions->isChecked(), $status->getValue() === 'active')) {
                    return new RedirectResponse('/shared-space');
                }

                $error = 'Failed to update member. Please try again.';
            }
        } else {
            /** @var Checkbox $permissions */
            $permissions = $form->get('permissions');
            $permissions->setChecked($member['isAdmin']);

            /** @var Radio $status */
            $status = $form->get('status');
            $status->setValue($member['isActive'] ? 'active' : 'inactive');
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/manage-member.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'member'    => $member,
                    'form'      => $form,
                    'error'     => $error,
                ],
            ),
        ));
    }
}
