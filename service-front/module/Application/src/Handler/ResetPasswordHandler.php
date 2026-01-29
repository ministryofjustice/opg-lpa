<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
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
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $token = $routeMatch?->getParam('token');

        if (empty($token)) {
            $html = $this->renderer->render(
                'application/general/forgot-password/invalid-reset-token.twig'
            );
            return new HtmlResponse($html);
        }

        $identity = $this->authenticationService->getIdentity();
        if ($identity !== null) {
            $this->sessionManagerSupport->getSessionManager()->getStorage()->clear();
            $this->sessionManagerSupport->initialise();
            return new RedirectResponse('/forgot-password/reset/' . $token);
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\SetPassword');
        $form->setAttribute('action', '/forgot-password/reset/' . $token);

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

                if ($result === true) {
                    $this->flashMessenger->addSuccessMessage('Password successfully reset');
                    return new RedirectResponse('/login');
                }

                if ($result === 'invalid-token') {
                    $html = $this->renderer->render(
                        'application/general/forgot-password/invalid-reset-token.twig'
                    );
                    return new HtmlResponse($html);
                }

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
