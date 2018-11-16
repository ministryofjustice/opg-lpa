<?php

namespace App\Middleware\Flash;

use App\Handler\Traits\JwtTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Flash\Messages;

/**
 * http://zendframework.github.io/zend-expressive/cookbook/flash-messengers/
 *
 * Class SlimFlashMiddleware
 * @package App\Middleware\Flash
 */
class SlimFlashMiddleware implements MiddlewareInterface
{
    use JwtTrait;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $flashMsgsKey = 'flashMsgs';
        $flashMsgsInnerKey = 'flashMsgsInner';

        $flashStorage = $this->getTokenData($flashMsgsKey);

        if (is_null($flashStorage)) {
            $flashStorage = [];
        } elseif (!is_array($flashStorage)) {
            //  NOTE - Strange bug where arrays are retrieved from JWT data as stdClass
            $flashStorage = (array) $flashStorage;

            if (!is_array($flashStorage[$flashMsgsInnerKey])) {
                $flashStorage[$flashMsgsInnerKey] = (array) $flashStorage[$flashMsgsInnerKey];
            }
        }

        $response = $handler->handle($request->withAttribute('flash', new Messages($flashStorage, $flashMsgsInnerKey)));

        $this->addTokenData($flashMsgsKey, $flashStorage);

        return $response;
    }
}
