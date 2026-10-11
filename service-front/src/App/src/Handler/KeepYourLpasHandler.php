<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\KeepYourLpasForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\OneLoginOnboardingTrait;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * One Login onboarding for a user with an existing Make account: whether to keep its
 * LPAs and account information by signing in to it, or start again with a new account.
 */
class KeepYourLpasHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use OneLoginOnboardingTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->onboardingSession($request);

        if ($this->sessionManager->getPendingLink($session) === null) {
            return $this->signInAgain();
        }

        $onboarding = $this->sessionManager->getOnboarding($session);

        if ($onboarding->userType === null) {
            return new RedirectResponse('/which-best-describes-you');
        }

        if ($onboarding->hasExistingAccount !== true) {
            return new RedirectResponse('/existing-account');
        }

        /** @var KeepYourLpasForm $form */
        $form = $this->formElementManager->get(KeepYourLpasForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(is_array($postData) ? $postData : []);

            if ($form->isValid()) {
                $onboarding->keepExistingAccount = $form->get('keepExistingAccount')->getValue() === 'yes';
                $this->sessionManager->saveOnboarding($session, $onboarding);

                return new RedirectResponse($onboarding->keepExistingAccount ? '/link-account' : '/your-details');
            }
        } elseif ($onboarding->keepExistingAccount !== null) {
            $form->setData(['keepExistingAccount' => $onboarding->keepExistingAccount ? 'yes' : 'no']);
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/keep-your-lpas.twig',
            array_merge($this->getTemplateVariables($request), ['form' => $form]),
        ));
    }
}
