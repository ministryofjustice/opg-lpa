<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';
    private const SESSION_KEY_IDENTITY = 'identity';
    private const FLASH_KEY_WARNING = 'flash_warning';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        // If already authenticated, redirect to dashboard
        if ($session->has(self::SESSION_KEY_IDENTITY)) {
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
                // Capture pre-auth URL before clearing session
                $nextUrl = $session->get(self::SESSION_KEY_PRE_AUTH_URL);

                $formData = $form->getData();
                $email = is_array($formData) ? ($formData['email'] ?? '') : '';
                $password = is_array($formData) ? ($formData['password'] ?? '') : '';

                $result = $this->authenticationService
                    ->setEmail($email)
                    ->setPassword($password)
                    ->authenticate();

                if ($result->isValid()) {
                    // Regenerate session to prevent fixation
                    $session->regenerate();
                    $session->clear();

                    // Store identity in session
                    $identity = $result->getIdentity();
                    $session->set(self::SESSION_KEY_IDENTITY, [
                        'userId'         => $identity->id(),
                        'token'          => $identity->token(),
                        'tokenExpiresAt' => $identity->tokenExpiresAt()->format('c'),
                        'lastLogin'      => $identity->lastLogin()->format('c'),
                    ]);

                    if ($nextUrl !== null && is_string($nextUrl)) {
                        return new RedirectResponse($nextUrl);
                    }

                    if (in_array('inactivity-flags-cleared', $result->getMessages(), true)) {
                        $session->set(self::FLASH_KEY_WARNING, [
                            'Thanks for logging in. Your LPA account will stay open for another 9 months.',
                        ]);
                    }

                    return new RedirectResponse('/user/dashboard');
                }

                // Authentication failed — reset form keeping email
                $form = $this->getLoginForm();
                $form->setData(['email' => $email]);

                $authError = $result->getMessages();
                if (count($authError) > 0) {
                    $authError = array_pop($authError);
                }

                // Throttle brute-force attempts
                sleep(1);
            }
        }

        $state = $request->getAttribute('state');

        $isTimeout = ($state === 'timeout');
        $isInternalSystemError = ($state === 'internalSystemError');

        return new HtmlResponse(
            $this->renderer->render(
                'application/general/auth/index.twig',
                [
                    'form'                  => $form,
                    'authError'             => $authError,
                    'isTimeout'             => $isTimeout,
                    'isInternalSystemError' => $isInternalSystemError,
                ]
            )
        );
    }

    private function getLoginForm(): FormInterface
    {
        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\User\Login');
        $form->setAttribute('action', '/login');

        return $form;
    }
}
