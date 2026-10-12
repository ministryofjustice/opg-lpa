<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\WhichBestDescribesYouForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\UserType;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The first One Login onboarding page: whether the user makes LPAs for themselves,
 * family or friends, or for other people as part of their work. The answer is kept
 * in the session until the last onboarding page saves it.
 */
class WhichBestDescribesYouHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        if ($this->sessionManager->getPendingLink($session) === null) {
            $this->logger->warning('auth.onelogin.onboarding_missing_pending_link');

            return new RedirectResponse('/login');
        }

        /** @var WhichBestDescribesYouForm $form */
        $form       = $this->formElementManager->get(WhichBestDescribesYouForm::class);
        $onboarding = $this->sessionManager->getOnboarding($session);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody();
            $form->setData(is_array($postData) ? $postData : []);

            if ($form->isValid()) {
                $onboarding->userType = UserType::from((string) $form->get('userType')->getValue());
                $this->sessionManager->saveOnboarding($session, $onboarding);

                return new RedirectResponse('/link-or-create-account');
            }
        } else {
            $form->setData(['userType' => $onboarding->userType?->value]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/onboarding/which-best-describes-you.twig',
            array_merge($this->getTemplateVariables($request), ['form' => $form]),
        ));
    }
}
