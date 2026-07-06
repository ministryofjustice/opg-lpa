<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\UserDetails as UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmRegistrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // To account for safelinks and similar activating accounts before page render
        if ($request->getMethod() === RequestMethodInterface::METHOD_HEAD) {
            return new Response();
        }

        $token = $request->getAttribute('token');

        $data = [];

        if (empty($token)) {
            $data['error'] = 'invalid-token';
            return new HtmlResponse($this->renderer->render(
                'application/general/register/confirm.twig',
                $data
            ));
        }

        // Clear any existing session
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if ($session instanceof SessionInterface) {
            $session->clear();
            $session->regenerate();
        }

        // Activate the account
        $success = $this->userService->activateAccount($token);

        if (!$success) {
            $data['error'] = 'account-missing';
        }

        return new HtmlResponse($this->renderer->render(
            'application/general/register/confirm.twig',
            $data
        ));
    }
}
