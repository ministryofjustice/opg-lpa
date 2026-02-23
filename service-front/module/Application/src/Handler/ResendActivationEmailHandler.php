<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Form\User\ConfirmEmail;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResendActivationEmailHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Prevent authenticated users from accessing this page
        $identity = $request->getAttribute('identity');
        if ($identity !== null) {
            return new RedirectResponse('/user/dashboard');
        }

        /** @var ConfirmEmail $form */
        $form = $this->formElementManager->get(ConfirmEmail::class);
        $form->setAttribute('action', '/signup/resend-email');

        $data = [
            'form' => $form,
        ];

        // Handle POST request
        if ($request->getMethod() === 'POST') {
            $postData = $request->getParsedBody();
            $form->setData($postData);

            if ($form->isValid()) {
                $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);
                $email = $formData['email'];

                $result = $this->userService->resendActivateEmail($email);

                if ($result === true) {
                    // Set up form for another resend email if needed
                    $resendForm = $this->formElementManager->get(ConfirmEmail::class);
                    assert($resendForm instanceof FormInterface);
                    $resendForm->setAttribute('action', '/signup/resend-email');
                    $resendForm->setData([
                        'email'         => $email,
                        'email_confirm' => $email,
                    ]);

                    return new HtmlResponse($this->renderer->render(
                        'application/general/register/email-sent.twig',
                        [
                            'form' => $resendForm,
                            'email' => $email,
                        ]
                    ));
                } else {
                    $data['error'] = $result;
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/general/register/resend-email.twig',
            $data
        ));
    }
}
