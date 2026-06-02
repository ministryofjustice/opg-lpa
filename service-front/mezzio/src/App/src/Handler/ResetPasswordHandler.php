<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\UserDetails as UserService;
use App\View\Twig\FlashMessenger;
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

class ResetPasswordHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_IDENTITY = 'identity';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        $token = $request->getAttribute('token');

        if (!is_string($token) || $token === '' || !$this->isValidTokenFormat($token)) {
            return new HtmlResponse(
                $this->renderer->render('application/general/forgot-password/invalid-reset-token.twig')
            );
        }

        // If logged in, clear session and redirect back
        if ($session->has(self::SESSION_KEY_IDENTITY)) {
            $session->clear();
            $session->regenerate();
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
                    /** @var FlashMessagesInterface $flash */
                    $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                    $flash->flash(FlashMessenger::SUCCESS, ['Password successfully reset']);
                    return new RedirectResponse('/login');
                }

                if ($result === 'invalid-token') {
                    return new HtmlResponse(
                        $this->renderer->render('application/general/forgot-password/invalid-reset-token.twig')
                    );
                }

                $error = $result;
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                'application/general/forgot-password/reset-password.twig',
                [
                    'form'  => $form,
                    'error' => $error,
                ]
            )
        );
    }

    private function isValidTokenFormat(string $token): bool
    {
        return ctype_alnum($token);
    }
}
