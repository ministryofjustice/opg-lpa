<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\ExistingAccountForm;
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
 * One Login onboarding: whether the user already has a Make account, so new users
 * are not asked about linking one.
 */
class ExistingAccountHandler implements RequestHandlerInterface
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

        /** @var ExistingAccountForm $form */
        $form = $this->formElementManager->get(ExistingAccountForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(is_array($postData) ? $postData : []);

            if ($form->isValid()) {
                $onboarding->hasExistingAccount = $form->get('hasExistingAccount')->getValue() === 'yes';
                $this->sessionManager->saveOnboarding($session, $onboarding);

                return new RedirectResponse(
                    $onboarding->hasExistingAccount ? '/keep-your-lpas' : '/your-details',
                );
            }
        } elseif ($onboarding->hasExistingAccount !== null) {
            $form->setData(['hasExistingAccount' => $onboarding->hasExistingAccount ? 'yes' : 'no']);
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/existing-account.twig',
            array_merge($this->getTemplateVariables($request), ['form' => $form]),
        ));
    }
}
