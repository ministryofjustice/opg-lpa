<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\SharedSpace\SharedSpaceMemberForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
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

        $result = $this->sharedSpaceService->getMembers();
        $members = $result['members'] ?? [];

        $member = null;
        foreach ($members as $candidate) {
            if ($candidate['id'] === $memberId) {
                $member = $candidate;
                break;
            }
        }

        if ($member === null) {
            return new RedirectResponse('/shared-space/manage');
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get(SharedSpaceMemberForm::class);

        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);
        $error = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $isAdmin = $form->get('permissions')->getValue() === '1';

                if ($this->sharedSpaceService->updateMemberIsAdmin($memberId, $isAdmin)) {
                    return new RedirectResponse('/shared-space/manage');
                }

                $error = 'Failed to update member. Please try again.';
            }
        } else {
            $form->setData(['permissions' => ($member['isAdmin'] ?? false) ? '1' : '0']);
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/manage-member.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'member'    => $member,
                    'form'      => $form,
                    'csrfToken' => $csrfToken,
                    'error'     => $error,
                ],
            ),
        ));
    }
}
