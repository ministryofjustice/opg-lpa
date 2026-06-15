<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Service\Payment\GovPay\Client as GovPayClient;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;

class AlphagovPayClientFactory
{
    public function __invoke(ContainerInterface $container): GovPayClient
    {
        $config    = $container->get('config');
        $payConfig = $config['alphagov']['pay'] ?? [];

        return new GovPayClient([
            'apiKey'     => $payConfig['key'] ?? '',
            'httpClient' => new GuzzleClient(),
            'baseUrl'    => $payConfig['url'] ?? null,
        ]);
    }
}
