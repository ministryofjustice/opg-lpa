<?php

namespace ApplicationTest\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Transport\MailTransport;
use Application\Model\Service\Mail\Transport\MailTransportFactory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;
use Twig\Environment;

class MailTransportFactoryTest extends MockeryTestCase
{
    /**
     * @throws ContainerException
     */
    public function testMailTransportFactory() : void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn(['email' => ['sendgrid' => ['key' => 'value']]]);
        $container->shouldReceive('get')
            ->withArgs(['TwigEmailRenderer'])
            ->once()
            ->andReturn(Mockery::mock(Environment::class));

        $factory = new MailTransportFactory();
        $result = $factory($container, null, null);

        $this->assertInstanceOf(MailTransport::class, $result);
    }

    public function testMailTransportFactoryNoSendgridConfig() : void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn(['email' => ['sendgrid' => []]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sendgrid settings not found');

        $factory = new MailTransportFactory();
        $result = $factory($container, null, null);
        $this->assertInstanceOf(MailTransport::class, $result);
    }
}
