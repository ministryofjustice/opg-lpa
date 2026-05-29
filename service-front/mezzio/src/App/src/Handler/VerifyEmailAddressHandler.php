<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyEmailAddressHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        $session->clear();
        $session->regenerate();

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $token = $request->getAttribute('token');

        $success = false;
        if (is_string($token) && $token !== '') {
            $success = $this->userService->updateEmailUsingToken($token) === true;
        }

        if ($success) {
            $flash->flash('flash_success', [
                'Your email address was successfully updated. Please login with your new address.',
            ]);
        } else {
            $flash->flash('flash_error', [
                'There was an error updating your email address',
            ]);
        }

        return new RedirectResponse('/login');
    }
}
