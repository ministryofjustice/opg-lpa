<?php

namespace App\Middleware\Session;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class SessionMiddleware
 * @package App\Middleware\Session
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string, mixed>
     */
    private $jwtConfig;

    /**
     * SignInHandler constructor.
     * @param array<string, mixed> $jwtConfig
     */
    public function __construct(array $jwtConfig)
    {
        $this->jwtConfig = $jwtConfig;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //  If there is no existing JWT cookie then create a new blank JWT token
        if (!array_key_exists($this->jwtConfig['cookie'], $_COOKIE)) {
            $tokenPayloadIn = [];
            $token = JWT::encode($tokenPayloadIn, $this->jwtConfig['secret'], $this->jwtConfig['algo']);

            $request = $request->withHeader($this->jwtConfig['header'], 'Bearer ' . $token);
            $request = $request->withAttribute('token', $tokenPayloadIn);
        }

        return $handler->handle($request);
    }
}
