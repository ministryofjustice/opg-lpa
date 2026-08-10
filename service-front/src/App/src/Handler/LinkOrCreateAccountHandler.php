<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\User\LinkOrCreateAccountForm;
use App\Middleware\CsrfValidationMiddleware;
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
            $this->logger->warning('auth.onelogin.link_or_create_missing_pending_link');

            return new RedirectResponse('/login');
        }

        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        /** @var LinkOrCreateAccountForm $form */
        $form = $this->formElementManager->get(LinkOrCreateAccountForm::class);

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $redirectUrl = $form->get('choice')->getValue() === 'link'
                    ? '/link-account'
                    : 'TODO-create-account';

                return new RedirectResponse($redirectUrl);
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/authenticated/linking/link-or-create-account.twig',
            [
                'form' => $form,
                'csrfToken' => $csrfToken,
            ],
        ));
    }
}
