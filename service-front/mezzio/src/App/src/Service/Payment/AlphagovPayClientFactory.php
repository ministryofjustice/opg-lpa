<?php

declare(strict_types=1);

namespace App\Service\Payment;

use Alphagov\Pay\Client as AlphagovPayClient;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;

class AlphagovPayClientFactory
{
    public function __invoke(ContainerInterface $container): AlphagovPayClient
    {
        $config    = $container->get('config');
        $payConfig = $config['alphagov']['pay'] ?? [];

        return new AlphagovPayClient([
            'apiKey'     => $payConfig['key'] ?? '',
            'httpClient' => new GuzzleClient(),
            'baseUrl'    => $payConfig['url'] ?? null,
        ]);
    }
}
