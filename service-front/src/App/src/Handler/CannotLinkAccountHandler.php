<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\OneLoginOnboardingTrait;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class CannotLinkAccountHandler implements RequestHandlerInterface
{
    use OneLoginOnboardingTrait;
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly OneLoginSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $onboarding = $this->sessionManager->getOnboarding($session);

            $onboarding->hasExistingAccount = false;
            $this->sessionManager->saveOnboarding($session, $onboarding);

            return new RedirectResponse($this->urlHelper->generate('your-details'));
        }

        if ($this->sessionManager->getPendingLink($session) === null) {
            $this->logger->warning('auth.onelogin.cannot_link_missing_pending_link');

            return new RedirectResponse('/login');
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/cannot-link-account.twig',
            $this->getTemplateVariables($request)
        ));
    }
}
