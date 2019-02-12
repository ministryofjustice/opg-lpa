<?php

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionFactory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\Session\Exception\RuntimeException;

class SessionFactoryTest extends MockeryTestCase
{
    // TODO - Complete this and add more unit tests once DynomoDbClient is injected (LPA-3097)
//    /**
//     * @throws ContainerException
//     */
//    public function testMailTransportFactory() : void
//    {
//        /** @var ContainerInterface|MockInterface $container */
//        $container = Mockery::Mock(ContainerInterface::class);
//        $container->shouldReceive('get')
//            ->withArgs(['Config'])
//            ->once()
//            ->andReturn([
//                'session' => [
//                    'dynamodb' => ['client' => null]
//                ]
//            ]);
//
//        $uri = Mockery::mock(Uri::class);
//        $uri->shouldReceive('getHost')->andReturn('Test Host')->once();
//        $uri->shouldReceive('getPath')->andReturn('/test-path')->once();
//
//        $request = Mockery::mock();
//        $request->shouldReceive('getUri')->once()->andReturn($uri);
//
//        $container->shouldReceive('get')
//            ->withArgs(['Request'])
//            ->once()
//            ->andReturn($request);
//
//        $factory = new SessionFactory();
//        $result = $factory($container, null, null);
//
//        $this->assertInstanceOf(MailTransport::class, $result);
//    }

    /**
     * @throws ContainerException
     * @expectedException RuntimeException
     * @expectedExceptionMessage Session configuration setting not found
     */
    public function testMailTransportFactoryNoSessionConfig() : void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once();

        $factory = new SessionFactory();
        $result = $factory($container, null, null);

        $this->assertInstanceOf(MailTransport::class, $result);
    }
}
