<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Form\User\ChangePassword as ChangePasswordForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Listener\Attribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChangePasswordHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity === null) {
            return new RedirectResponse('/login');
        }

        $form = $this->formElementManager->get(ChangePasswordForm::class);
        assert($form instanceof ChangePasswordForm);
        $form->setAttribute('action', '/user/change-password');

        $error = null;

        // Get current email from user details (set by UserDetailsListener middleware)
        $userDetails = $request->getAttribute(Attribute::USER_DETAILS);
        $currentEmailAddress = (string) $userDetails->email;

        // This form needs to check the user's current password, thus we pass it the Authentication Service
        $this->authenticationService->setEmail($currentEmailAddress);
        $form->setAuthenticationService($this->authenticationService);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $validated = $form->getData();

                $currentPassword = '';
                $newPassword = '';

                if (is_array($validated)) {
                    if (isset($validated['password_current']) && is_string($validated['password_current'])) {
                        $currentPassword = $validated['password_current'];
                    }
                    if (isset($validated['password']) && is_string($validated['password'])) {
                        $newPassword = $validated['password'];
                    }
                }

                $result = $this->userService->updatePassword($currentPassword, $newPassword);

                if ($result === true) {
                    $this->flashMessenger->addSuccessMessage(
                        'Your new password has been saved. ' .
                        'Please remember to use this new password to sign in from now on.'
                    );
                    return new RedirectResponse('/user/about-you');
                } else {
                    $error = $result;
                }
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/change-password/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'error' => $error,
                    'pageTitle' => 'Change your password',
                    'cancelUrl' => '/user/about-you',
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
