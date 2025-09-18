<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Transport\NotifyMailTransport;
use Application\Model\Service\Mail\Transport\MailTransportFactory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;

final class MailTransportFactoryTest extends MockeryTestCase
{
    /**
     * @throws ContainerException
     */

    public function testMailTransportFactoryNotify(): void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);

        // value for the Notify API key has to be a valid UUID4;
        // or at least, what the Notify PHP client thinks is a valid UUID4, which
        // is a string matching:
        //     /[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12}/
        // (not all valid UUID4s match this string)
        $apiKey = '31201bcc-e36d-4845-abb9-ce72eb7d0a93';

        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn(['email' => ['transport' => 'notify', 'notify' => ['key' => $apiKey]]]);

        $result = (new MailTransportFactory())($container, null, null);

        $this->assertInstanceOf(NotifyMailTransport::class, $result);
    }

    public function testMailTransportFactoryNoNotifyConfig(): void
    {
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn(['email' => ['transport' => 'notify']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Notify API settings not found');

        $factory = new MailTransportFactory();
        $factory($container, null, null);
    }

    public function testMailTransportFactoryNoTransportInConfig(): void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn(['email' => []]);

        $this->expectException(RuntimeException::class);
        (new MailTransportFactory())($container, null, null);
    }
}
