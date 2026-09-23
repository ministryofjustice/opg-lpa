<?php

declare(strict_types=1);

namespace App\Handler;

use App\Feature;
use App\Handler\Traits\CommonTemplateVariablesTrait;
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
    use CommonTemplateVariablesTrait;

    private const SESSION_KEY_IDENTITY = 'identity';
    private const SHARED_SPACE_TOKEN_PREFIX = 'sharedspace';

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

        $destination = Feature::OneLogin->isEnabled() ? '/home' : '/login';

        if ($session->has(self::SESSION_KEY_IDENTITY)) {
            if (str_starts_with($token, self::SHARED_SPACE_TOKEN_PREFIX)) {
                $destination = '/shared-space/dashboard';
            } else {
                // If logged in, clear session and redirect back
                $session->clear();
                $session->regenerate();
                return new RedirectResponse('/forgot-password/reset/' . $token);
            }
        }

        /** @var FormInterface $form */
        $form = $this->formElementManager->get(\App\Form\User\SetPassword::class);
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

                    return new RedirectResponse($destination);
                }

                if ($result === 'invalid-token') {
                    return new HtmlResponse(
                        $this->renderer->render(
                            'application/general/forgot-password/invalid-reset-token.twig',
                            $this->getTemplateVariables($request),
                        )
                    );
                }

                $error = $result;
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                'application/general/forgot-password/reset-password.twig',
                array_merge(
                    $this->getTemplateVariables($request),
                    [
                        'form'  => $form,
                        'error' => $error,
                    ],
                ),
            )
        );
    }

    private function isValidTokenFormat(string $token): bool
    {
        return ctype_alnum($token);
    }
}
