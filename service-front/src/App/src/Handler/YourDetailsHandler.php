<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\AboutYou;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\OneLoginOnboardingTrait;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use DateTime;
use DateTimeZone;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The last One Login onboarding page for a user new to Make. Saving it creates their
 * Make account with the answers given during onboarding and the details entered here.
 */
class YourDetailsHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use OneLoginOnboardingTrait;

    /**
     * The AboutYou form validates against the whole User model, which needs an id and
     * timestamps that the API only sets when it creates the account. This placeholder
     * id stands in for it, so only the user's own details are checked here.
     */
    private const string PLACEHOLDER_ID = '00000000000000000000000000000000';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly OneLoginService $oneLoginService,
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

        if (!$onboarding->createsNewAccount()) {
            return new RedirectResponse('/existing-account');
        }

        /** @var AboutYou $form */
        $form = $this->formElementManager->get(AboutYou::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(array_merge(is_array($postData) ? $postData : [], $this->serverSetFields()));

            if ($form->isValid()) {
                /** @var array<string, mixed> $details */
                $details = $form->getModelDataFromValidatedForm();

                try {
                    $identity = $this->oneLoginService->createAndLinkAccount(
                        $pendingLink->sub,
                        $pendingLink->email,
                        $onboarding->userType,
                        array_intersect_key($details, array_flip(['name', 'dob', 'address'])),
                    );

                    $this->logger->info('auth.onelogin.create_and_link_success');

                    return new RedirectResponse(
                        $this->sessionManager->finishOnboarding($session, $identity, $pendingLink->idToken),
                    );
                } catch (RuntimeException $e) {
                    $this->logger->error('auth.onelogin.create_error', ['message' => $e->getMessage()]);
                    $error = 'api-error';
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/your-details.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'      => $form,
                    'error'     => $error ?? null,
                    'backRoute' => $onboarding->hasExistingAccount ? 'keep-your-lpas' : 'existing-account',
                ],
            ),
        ));
    }

    /**
     * @return array{id: string, createdAt: DateTime, updatedAt: DateTime}
     */
    private function serverSetFields(): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        return ['id' => self::PLACEHOLDER_ID, 'createdAt' => $now, 'updatedAt' => $now];
    }
}
