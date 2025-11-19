<?php

declare(strict_types=1);

namespace Application\Flash;

use Mezzio\Flash\FlashMessages;
use Psr\Container\ContainerInterface;
use Mezzio\Flash\FlashMessageMiddleware;

class FlashMessageMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new FlashMessageMiddleware(
            FlashMessages::class,
            FlashMessages::class . '::FLASH_NEXT',
            'flash-messages'
        );
    }
}
