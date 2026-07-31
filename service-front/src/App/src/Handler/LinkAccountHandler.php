<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\User\Login;
use App\Handler\Traits\OneLoginPendingLinkTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\UserDetails;
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

class LinkAccountHandler implements RequestHandlerInterface
{
    use OneLoginPendingLinkTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly UserDetails $userDetails,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        $sub = $this->pendingLinkSub($session);

        if ($sub === null) {
            $this->logger->warning('auth.onelogin.link_missing_pending_sub');

            return new RedirectResponse('/login');
        }

        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

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
                $result = $this->authenticationService
                    ->setEmail($form->get('email')->getValue())
                    ->setPassword($form->get('password')->getValue())
                    ->authenticate();

                if ($result->isValid()) {
                    if ($this->userDetails->setOneLoginSub($sub)) {
                        $session->unset(self::SESSION_KEY_PENDING_LINK);

                        $this->logger->info('auth.onelogin.link_success');

                        return new RedirectResponse('/user/dashboard');
                    }

                    $this->logger->error('auth.onelogin.link_persist_failed');

                    $authError = 'link-failed';
                } else {
                    $messages = $result->getMessages();
                    $authError = count($messages) > 0 ? (string) array_pop($messages) : 'authentication-failed';

                    // Throttle brute-force attempts, mirroring LoginHandler.
                    sleep(1);
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-account.twig',
            [
                'form' => $form,
                'csrfToken' => $csrfToken,
                'authError' => $authError,
            ],
        ));
    }
}
