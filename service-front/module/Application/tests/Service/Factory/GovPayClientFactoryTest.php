<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Alphagov\Pay\Client as GovPayClient;
use Application\Service\Factory\GovPayClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class GovPayClientFactoryTest extends TestCase
{
    public function testFactoryReturnsGovPayClient(): void
    {
        $config = [
            'alphagov' => [
                'pay' => [
                    'key' => 'test-api-key',
                    'url' => 'https://publicapi.payments.service.gov.uk',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'     => $config,
            // Http\Adapter\Guzzle7\Client is final — create a real instance.
            'HttpClient' => new \Http\Adapter\Guzzle7\Client(),
        });

        $client = (new GovPayClientFactory())($container);

        $this->assertInstanceOf(GovPayClient::class, $client);
    }
}
