<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\LinkOrCreateAccountForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LinkOrCreateAccountHandler implements RequestHandlerInterface
{
    private const string SESSION_KEY_IDENTITY     = 'identity';
    private const string SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';

    use CommonTemplateVariablesTrait;

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
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        $pendingLink = $this->sessionManager->getPendingLink($session);

        if ($pendingLink === null) {
            $this->logger->warning('auth.onelogin.link_or_create_missing_pending_link');

            return new RedirectResponse('/login');
        }

        /** @var LinkOrCreateAccountForm $form */
        $form = $this->formElementManager->get(LinkOrCreateAccountForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                if ($form->get('choice')->getValue() === 'link') {
                    return new RedirectResponse('/link-account');
                }

                $identity = $this->oneLoginService->createAndLinkAccount($pendingLink->sub);

                return $this->establishSession($session, $identity);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-or-create-account.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                ]
            )
        ));
    }

    /**
     * @param array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string} $identity
     */
    private function establishSession(SessionInterface $session, array $identity): RedirectResponse
    {
        $preAuthUrl = $session->get(self::SESSION_KEY_PRE_AUTH_URL);

        $session->regenerate();
        $session->clear();
        $session->set(self::SESSION_KEY_IDENTITY, $identity);

        $this->logger->info('auth.onelogin.create_and_link_success');

        if (is_string($preAuthUrl) && $preAuthUrl !== '') {
            return new RedirectResponse($preAuthUrl);
        }

        return new RedirectResponse('/user/dashboard');
    }
}
