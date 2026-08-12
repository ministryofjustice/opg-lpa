<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\Login;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\LinkReason;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LinkAccountHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    private const string SESSION_KEY_IDENTITY     = 'identity';
    private const string SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';

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
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        $pendingLink = $this->sessionManager->getPendingLink($session);

        if ($pendingLink === null) {
            $this->logger->warning('auth.onelogin.link_missing_pending_sub');

            return new RedirectResponse('/login');
        }

        /** @var Login $form */
        $form = $this->formElementManager->get(Login::class);

        $authError = null;

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                try {
                    $result = $this->oneLoginService->linkExistingAccount(
                        (string) $form->get('email')->getValue(),
                        (string) $form->get('password')->getValue(),
                        $pendingLink->sub,
                        $pendingLink->email,
                    );
                } catch (RuntimeException $e) {
                    $this->logger->error('auth.onelogin.link_error', ['message' => $e->getMessage()]);

                    $result = ['linked' => false, 'reason' => 'api-error'];
                }

                if ($result['linked'] === true) {
                    return $this->establishSession($session, $result['identity']);
                }

                $reason = $result['reason'];

                if ($reason === LinkReason::ALREADY_LINKED) {
                    $this->logger->warning('auth.onelogin.link_rejected', ['reason' => $reason]);

                    return new RedirectResponse('/cannot-link-account');
                }

                $this->logger->info('auth.onelogin.link_failed', ['reason' => $reason]);

                $authError = $this->mapReasonToAuthError($reason);

                // Throttle brute-force attempts, mirroring LoginHandler.
                sleep(1);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-account.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'authError' => $authError,
                ],
            )
        ));
    }

    /**
     * Map the API's rejection reason to the inline error key the template renders.
     */
    private function mapReasonToAuthError(string $reason): string
    {
        return match ($reason) {
            LinkReason::ACCOUNT_LOCKED     => 'locked',
            LinkReason::ACCOUNT_NOT_ACTIVE => 'not-activated',
            'api-error'                    => 'api-error',
            // invalid-credentials, account-not-found, account-deleted, unknown:
            // shown as the generic "not recognised" message to avoid disclosing
            // whether the account exists.
            default                        => 'authentication-failed',
        };
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

        $this->logger->info('auth.onelogin.link_success');

        if (is_string($preAuthUrl) && $preAuthUrl !== '') {
            return new RedirectResponse($preAuthUrl);
        }

        return new RedirectResponse('/user/dashboard');
    }
}
