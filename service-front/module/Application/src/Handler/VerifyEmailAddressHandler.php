<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyEmailAddressHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UserService $userService,
        private readonly SessionManagerSupport $sessionManagerSupport,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->sessionManagerSupport->getSessionManager()->getStorage()->clear();
        $this->sessionManagerSupport->initialise();

        $routeMatch = $request->getAttribute(RouteMatch::class);
        $token = $routeMatch?->getParam('token');

        $success = false;
        if (is_string($token) && $token !== '') {
            $success = $this->userService->updateEmailUsingToken($token) === true;
        }

        if ($success) {
            $this->flashMessenger->addSuccessMessage(
                'Your email address was successfully updated. Please login with your new address.'
            );
        } else {
            $this->flashMessenger->addErrorMessage(
                'There was an error updating your email address'
            );
        }

        return new RedirectResponse('/login');
    }
}
