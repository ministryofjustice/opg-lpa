<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Cache\Cache;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class SystemMessageHandlerFactory
 * @package App\Handler
 */
class SystemMessageHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $cache = $container->get(Cache::class);

        return new SystemMessageHandler($cache);
    }
}
