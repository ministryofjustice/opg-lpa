<?php

namespace App\Middleware\Session;

use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CsrfMiddleware
 * @package App\Middleware\Session
 */
class CsrfMiddleware implements MiddlewareInterface
{
    use JwtTrait;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $csrf = $this->getTokenData('csrf');

        if (is_null($csrf)) {
            //  Generate a secret csrf value before proceeding
            $secret = gmp_strval(gmp_init(bin2hex(random_bytes(64)), 10), 62);

            $this->addTokenData('csrf', $secret);
        }

        return $handler->handle($request);
    }
}
