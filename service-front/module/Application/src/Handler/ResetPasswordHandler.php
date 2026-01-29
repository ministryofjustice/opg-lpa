<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\SessionManager;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResetPasswordHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
        private readonly UrlHelper $urlHelper,
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManager $sessionManager,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get token from route
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $matchedParams = $routeResult?->getMatchedParams() ?? [];
        $token = $matchedParams['token'] ?? null;

        if (empty($token)) {
            $html = $this->renderer->render(
                'application/general/forgot-password/invalid-reset-token.twig'
            );
            return new HtmlResponse($html);
        }

        // If there's currently a signed-in user, log them out and redirect
        $identity = $this->authenticationService->getIdentity();
        if ($identity !== null) {
            // Clear session storage
            $this->sessionManager->getStorage()->clear();

            // Redirect to the same page with a new CSRF token
            return new RedirectResponse(
                $this->urlHelper->generate('forgot-password/callback', ['token' => $token])
            );
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\SetPassword');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('forgot-password/callback', ['token' => $token])
        );

        $error = null;

        if (strtoupper($request->getMethod()) === 'POST') {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $formData = $form->getData();
                $password = is_array($formData) ? ($formData['password'] ?? '') : '';

                $result = $this->userService->setNewPassword($token, $password);

                // If successful, redirect to login with flash message
                if ($result === true) {
                    $this->flashMessenger->addSuccessMessage('Password successfully reset');

                    return new RedirectResponse($this->urlHelper->generate('login'));
                }

                if ($result === 'invalid-token') {
                    $html = $this->renderer->render(
                        'application/general/forgot-password/invalid-reset-token.twig'
                    );
                    return new HtmlResponse($html);
                }

                // Otherwise there was an error
                $error = $result;
            }
        }

        $html = $this->renderer->render(
            'application/general/forgot-password/reset-password.twig',
            [
                'form' => $form,
                'error' => $error,
            ]
        );

        return new HtmlResponse($html);
    }
}
