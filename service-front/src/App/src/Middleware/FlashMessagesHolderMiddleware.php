<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\FlashMessagesHolder;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Transfers the FlashMessagesInterface instance (set on the request by
 * FlashMessageMiddleware) into the shared FlashMessagesHolder singleton,
 * making it available to Twig extensions without requiring request access.
 */
class FlashMessagesHolderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly FlashMessagesHolder $holder,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        if ($flash instanceof FlashMessagesInterface) {
            $this->holder->set($flash);
        }

        return $handler->handle($request);
    }
}
