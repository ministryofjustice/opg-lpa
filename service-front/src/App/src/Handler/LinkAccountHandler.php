<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\LinkAccountForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\OneLoginOnboardingTrait;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\LinkReason;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * One Login onboarding for a user keeping their existing Make account: they sign in to it
 * so it can be linked to their GOV.UK One Login.
 */
class LinkAccountHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use OneLoginOnboardingTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly OneLoginService $oneLoginService,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session     = $this->onboardingSession($request);
        $pendingLink = $this->sessionManager->getPendingLink($session);

        if ($pendingLink === null) {
            return $this->signInAgain();
        }

        $onboarding = $this->sessionManager->getOnboarding($session);

        if ($onboarding->userType === null) {
            return new RedirectResponse('/which-best-describes-you');
        }

        if (!$onboarding->linksExistingAccount()) {
            return new RedirectResponse('/keep-your-lpas');
        }

        /** @var LinkAccountForm $form */
        $form = $this->formElementManager->get(LinkAccountForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(is_array($postData) ? $postData : []);

            if ($form->isValid()) {
                try {
                    $result = $this->oneLoginService->linkExistingAccount(
                        (string) $form->get('email')->getValue(),
                        (string) $form->get('password')->getValue(),
                        $pendingLink->sub,
                        $pendingLink->email,
                        $onboarding->userType,
                    );
                } catch (RuntimeException $e) {
                    $this->logger->error('auth.onelogin.link_error', ['message' => $e->getMessage()]);

                    return new RedirectResponse('/cannot-link-account');
                }

                if ($result['linked'] === true) {
                    $this->logger->info('auth.onelogin.link_success');

                    return new RedirectResponse(
                        $this->sessionManager->finishOnboarding($session, $result['identity'], $pendingLink->idToken),
                    );
                }

                if ($result['reason'] === LinkReason::ALREADY_LINKED) {
                    $this->logger->warning('auth.onelogin.link_rejected', ['reason' => $result['reason']]);
                } else {
                    $this->logger->info('auth.onelogin.link_failed', ['reason' => $result['reason']]);

                    // Throttle brute-force attempts, mirroring LoginHandler.
                    sleep(1);
                }

                // Every failure goes to the same page, so it never reveals whether an account exists.
                return new RedirectResponse('/cannot-link-account');
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/link-account.twig',
            array_merge($this->getTemplateVariables($request), ['form' => $form]),
        ));
    }
}
