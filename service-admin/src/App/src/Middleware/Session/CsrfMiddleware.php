<?php

namespace App\Middleware\Session;

use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Math\BigInteger\BigInteger;

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
            $secret = BigInteger::factory('bcmath')->baseConvert(
                bin2hex(random_bytes(64)),
                16,
                62
            );

            $this->addTokenData('csrf', $secret);
        }

        return $handler->handle($request);
    }
}
