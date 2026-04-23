<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Http\Adapter\Guzzle7\Client;
use Psr\Container\ContainerInterface;

class HttpClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        return new Client();
    }
}
