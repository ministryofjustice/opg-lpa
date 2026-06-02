<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\User\ChangePassword as ChangePasswordForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\UserDetails as UserService;
use App\View\Twig\FlashMessenger;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        $form = $this->formElementManager->get(ChangePasswordForm::class);
        assert($form instanceof ChangePasswordForm);
        $form->setAttribute('action', '/user/change-password');

        $error = null;

        // Get current email from user details (set by UserDetailsMiddleware middleware)
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);
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
                    /** @var FlashMessagesInterface $flash */
                    $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                    $flash->flash(FlashMessenger::SUCCESS, [
                        'Your new password has been saved. ' .
                        'Please remember to use this new password to sign in from now on.',
                    ]);
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
                    'form'      => $form,
                    'error'     => $error,
                    'pageTitle' => 'Change your password',
                    'cancelUrl' => '/user/about-you',
                    'csrfToken' => $csrfToken,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
