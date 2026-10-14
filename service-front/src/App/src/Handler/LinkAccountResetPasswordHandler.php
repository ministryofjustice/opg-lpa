<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\ConfirmEmail;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\OneLoginOnboardingTrait;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\UserDetails as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * One Login onboarding for a user who has forgotten the password to the Make account they
 * want to keep.
 */
class LinkAccountResetPasswordHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use OneLoginOnboardingTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->sessionManager->getPendingLink($this->getSession($request)) === null) {
            return $this->signInAgain();
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get(ConfirmEmail::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(is_array($postData) ? $postData : []);

            if ($form->isValid()) {
                $email  = $form->getData(FormInterface::VALUES_AS_ARRAY)['email'];
                $result = $this->userService->requestPasswordResetEmail($email);

                return new HtmlResponse($this->renderer->render(
                    'application/general/forgot-password/email-sent.twig',
                    [
                        'email'               => $email,
                        'accountNotActivated' => $result === 'account-not-activated',
                    ],
                ));
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/reset-password.twig',
            array_merge($this->getTemplateVariables($request), ['form' => $form]),
        ));
    }
}
