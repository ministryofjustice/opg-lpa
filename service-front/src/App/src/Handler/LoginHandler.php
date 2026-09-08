<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\User\Login;
use App\Middleware\AuthenticationMiddleware;
use App\Service\SafeRedirectPath;
use App\View\Twig\FlashMessenger;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_IDENTITY = 'identity';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly AuthenticationService $authenticationService,
        private readonly bool $oneLoginEnabled = false,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        // If already authenticated, redirect to dashboard
        if ($session->has(self::SESSION_KEY_IDENTITY)) {
            $existingIdentity = $session->get(self::SESSION_KEY_IDENTITY);
            $sharedSpaceId = is_array($existingIdentity) ? ($existingIdentity['sharedSpaceId'] ?? null) : null;

            return new RedirectResponse($sharedSpaceId !== null ? '/shared-space/dashboard' : '/user/dashboard');
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
                $nextUrl = SafeRedirectPath::filter($session->get(AuthenticationMiddleware::SESSION_KEY_PRE_AUTH_URL));

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
                        'sharedSpaceId'  => $identity->getSharedSpaceId(),
                    ]);

                    if ($nextUrl !== null) {
                        return new RedirectResponse($nextUrl);
                    }

                    if (in_array('inactivity-flags-cleared', $result->getMessages(), true)) {
                        /** @var FlashMessagesInterface $flash */
                        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                        $flash->flash(FlashMessenger::WARNING, [
                            'Thanks for logging in. Your LPA account will stay open for another 9 months.',
                        ]);
                    }

                    return new RedirectResponse(
                        $identity->getSharedSpaceId() !== null ? '/shared-space/dashboard' : '/user/dashboard'
                    );
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

        $isTimeout = $state === 'timeout';
        $isInternalSystemError = $state === 'internal-system-error';

        return new HtmlResponse(
            $this->renderer->render(
                'application/general/auth/index.twig',
                [
                    'form'                  => $form,
                    'authError'             => $authError,
                    'isTimeout'             => $isTimeout,
                    'isInternalSystemError' => $isInternalSystemError,
                    'oneLoginEnabled'       => $this->oneLoginEnabled,
                ]
            )
        );
    }

    private function getLoginForm(): FormInterface
    {
        /** @var FormInterface $form */
        $form = $this->formElementManager->get(Login::class);
        $form->setAttribute('action', '/login');

        return $form;
    }
}
