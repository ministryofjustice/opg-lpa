<?php

declare(strict_types=1);

namespace App\Authentication;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AuthenticationServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationService
    {
        $config = $container->get('config');
        $apiUri = $config['api_client']['api_uri'] ?? null;

        $apiClient = new ApiClient(
            new GuzzleClient(),
            (string) $apiUri,
        );
        $apiClient->setLogger($container->get(LoggerInterface::class));

        return new AuthenticationService(new LpaAuthAdapter($apiClient));
    }
}
