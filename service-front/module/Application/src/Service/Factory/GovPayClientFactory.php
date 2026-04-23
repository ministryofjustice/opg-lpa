<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Alphagov\Pay\Client as GovPayClient;
use Psr\Container\ContainerInterface;

class GovPayClientFactory
{
    public function __invoke(ContainerInterface $container): GovPayClient
    {
        $config = $container->get('config')['alphagov']['pay'];

        return new GovPayClient([
            'apiKey'     => $config['key'],
            'httpClient' => $container->get('HttpClient'),
            'baseUrl'    => $config['url'],
        ]);
    }
}
