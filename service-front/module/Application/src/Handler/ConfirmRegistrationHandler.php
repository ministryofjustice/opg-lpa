<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Router\RouteMatch;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmRegistrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly UserService $userService,
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $token = $routeMatch?->getParam('token');

        $data = [];

        if (empty($token)) {
            $data['error'] = 'invalid-token';
            return new HtmlResponse($this->renderer->render(
                'application/general/register/confirm.twig',
                $data
            ));
        }

        // Ensure they're not logged in whilst activating a new account
        $this->authenticationService->clearIdentity();

        // Clear the session
        $this->sessionManagerSupport->getSessionManager()->getStorage()->clear();
        $this->sessionManagerSupport->initialise();

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
