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

    public function __construct(
        private readonly OneLoginService $oneLoginService,
        private readonly RedirectUriBuilder $redirectUriBuilder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $redirectUri = ($this->redirectUriBuilder)($request->getUri());

        $result = $this->oneLoginService->start($redirectUri);

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        $session->set(self::SESSION_KEY_ONELOGIN, [
            'state'        => $result['state'],
            'nonce'        => $result['nonce'],
            'redirect_uri' => $redirectUri,
        ]);

        return new RedirectResponse($result['url']);
    }
}
