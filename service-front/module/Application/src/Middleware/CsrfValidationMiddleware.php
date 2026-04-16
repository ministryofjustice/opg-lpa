<?php

declare(strict_types=1);

namespace Application\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfValidationMiddleware implements MiddlewareInterface
{
    public const TOKEN_ATTRIBUTE = 'csrfToken';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $token = ($request->getParsedBody() ?? [])['__csrf'] ?? '';

            if (!$guard->validateToken($token)) {
                return new RedirectResponse($request->getUri()->getPath());
            }
        }

        // Generate a fresh token and make it available to downstream handlers/templates
        $request = $request->withAttribute(self::TOKEN_ATTRIBUTE, $guard->generateToken());

        return $handler->handle($request);
    }
}
