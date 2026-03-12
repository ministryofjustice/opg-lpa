<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\Session\ContainerNamespace;
use Fig\Http\Message\RequestMethodInterface;
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

class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport,
        private readonly SessionUtility $sessionUtility,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity !== null) {
            return new RedirectResponse('/user/dashboard');
        }

        $form = $this->getLoginForm();

        $authError = null;

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $nextUrl = $this->sessionUtility->getFromMvc(ContainerNamespace::PRE_AUTH_REQUEST, 'url');

                $sessionManager = $this->sessionManagerSupport->getSessionManager();
                $sessionManager->getStorage()->clear();
                $this->sessionManagerSupport->initialise();

                $formData = $form->getData();
                $email = is_array($formData) ? ($formData['email'] ?? '') : '';
                $password = is_array($formData) ? ($formData['password'] ?? '') : '';

                $result = $this->authenticationService
                    ->setEmail($email)
                    ->setPassword($password)
                    ->authenticate();

                if ($result->isValid()) {
                    $sessionManager->regenerateId(true);

                    if (isset($nextUrl)) {
                        return new RedirectResponse($nextUrl);
                    }

                    if (in_array('inactivity-flags-cleared', $result->getMessages())) {
                        $this->flashMessenger->addWarningMessage(
                            'Thanks for logging in. Your LPA account will stay open for another 9 months.'
                        );
                    }

                    return new RedirectResponse('/user/dashboard');
                }

                $form = $this->getLoginForm();
                $form->setData(['email' => $email]);

                $authError = $result->getMessages();

                if (count($authError) > 0) {
                    $authError = array_pop($authError);
                }

                sleep(1);
            }
        }

        $routeMatch = $request->getAttribute(RouteMatch::class);
        $state = $routeMatch?->getParam('state');

        $isTimeout = ($state === 'timeout');
        $isInternalSystemError = ($state === 'internalSystemError');

        $html = $this->renderer->render(
            'application/general/auth/index.twig',
            [
                'form' => $form,
                'authError' => $authError,
                'isTimeout' => $isTimeout,
                'isInternalSystemError' => $isInternalSystemError,
            ]
        );

        return new HtmlResponse($html);
    }

    private function getLoginForm(): FormInterface
    {
        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\Login');
        $form->setAttribute('action', '/login');

        return $form;
    }
}
