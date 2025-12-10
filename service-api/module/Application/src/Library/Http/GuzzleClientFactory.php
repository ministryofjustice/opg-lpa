<?php

declare(strict_types=1);

namespace Application\Library\Http;

use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class GuzzleClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Client
    {
        return new Client();
    }
}
