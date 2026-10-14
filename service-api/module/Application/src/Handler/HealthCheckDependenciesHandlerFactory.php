<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class HealthCheckDependenciesHandlerFactory
{
    public function __invoke(ContainerInterface $container): HealthCheckDependenciesHandler
    {
        $config = $container->get('config');

        return new HealthCheckDependenciesHandler(
            $container->get(ClientInterface::class),
            $container->get(CredentialsInterface::class),
            $container->get(SignatureV4::class),
            $container->get(LoggerInterface::class),
            $config,
        );
    }
}
