<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class OneLoginSignInHandler implements RequestHandlerInterface
{
    private const string SESSION_KEY_ONELOGIN = 'onelogin_auth';
    private const string SESSION_KEY_IDENTITY = 'identity';

    public function __construct(
        private readonly OneLoginService $oneLoginService,
        private readonly RedirectUriBuilder $redirectUriBuilder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        if ($session->has(self::SESSION_KEY_IDENTITY)) {
            return new RedirectResponse('/user/dashboard');
        }

        $redirectUri = ($this->redirectUriBuilder)($request->getUri());

        $result = $this->oneLoginService->start($redirectUri);

        $session->set(self::SESSION_KEY_ONELOGIN, [
            'state'        => $result['state'],
            'nonce'        => $result['nonce'],
            'redirect_uri' => $redirectUri,
        ]);

        return new RedirectResponse($result['url']);
    }
}
