<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForgotPasswordHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $request->getAttribute('identity');
        if ($identity !== null) {
            return new RedirectResponse('/user/dashboard');
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\ConfirmEmail');
        $form->setAttribute('action', '/forgot-password');

        $error = null;

        if (strtoupper($request->getMethod()) === 'POST') {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);

                $result = $this->userService->requestPasswordResetEmail($formData['email']);

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
            [
                'form' => $form,
                'error' => $error,
            ]
        );

        return new HtmlResponse($html);
    }
}
