<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyEmailAddressHandler implements RequestHandlerInterface
{
    private const FLASH_KEY_SUCCESS = 'flash_success';
    private const FLASH_KEY_ERROR = 'flash_error';

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

        $token = $request->getAttribute('token');

        $success = false;
        if (is_string($token) && $token !== '') {
            $success = $this->userService->updateEmailUsingToken($token) === true;
        }

        if ($success) {
            $session->set(self::FLASH_KEY_SUCCESS, [
                'Your email address was successfully updated. Please login with your new address.',
            ]);
        } else {
            $session->set(self::FLASH_KEY_ERROR, [
                'There was an error updating your email address',
            ]);
        }

        return new RedirectResponse('/login');
    }
}
