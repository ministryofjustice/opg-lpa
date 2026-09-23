<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\UserDetails as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForgotPasswordHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $forSharedSpace = $request->getAttribute(RouteResult::class)?->getMatchedRouteName() === 'shared-space.forgot-password';

        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        if (!$forSharedSpace && $identity !== null) {
            return new RedirectResponse('/user/dashboard');
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get(\App\Form\User\ConfirmEmail::class);
        $form->setAttribute('action', ($forSharedSpace ? '/shared-space' : '') . '/forgot-password');

        $error = null;

        if (strtoupper($request->getMethod()) === 'POST') {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);

                $result = $this->userService->requestPasswordResetEmail($formData['email'], $forSharedSpace);

                $viewParams = [
                    'email' => $formData['email'],
                    'accountNotActivated' => ($result === 'account-not-activated'),
                ];

                $html = $this->renderer->render(
                    'application/general/forgot-password/email-sent.twig',
                    $viewParams
                );

                return new HtmlResponse($html);
            }
        }

        $html = $this->renderer->render(
            'application/general/forgot-password/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'  => $form,
                    'error' => $error,
                ],
            ),
        );

        return new HtmlResponse($html);
    }
}
