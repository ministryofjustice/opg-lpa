<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OneLoginSignInHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_ONELOGIN = 'onelogin_auth';

    public function __construct(
        private readonly OneLoginService $oneLoginService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri         = $request->getUri();
        $redirectUri = $uri->getScheme() . '://' . $uri->getAuthority() . '/auth/redirect';

        $result = $this->oneLoginService->start($redirectUri);

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        $session->set(self::SESSION_KEY_ONELOGIN, [
            'state' => $result['state'],
            'nonce' => $result['nonce'],
        ]);

        return new RedirectResponse($result['url']);
    }
}
