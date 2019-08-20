<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\PingController;
use Application\ControllerFactory\PingControllerFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\Db\Adapter\Adapter;
use Aws\Sqs\SqsClient;
use Http\Client\HttpClient;

class PingControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var PingControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new PingControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with(HttpClient::class)
            ->andReturn(Mockery::mock(HttpClient::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with('ZendDbAdapter')
            ->andReturn(Mockery::mock(Adapter::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with('SqsClient')
            ->andReturn(Mockery::mock(SqsClient::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with('config')
            ->andReturn([
                'pdf'=>['queue'=>['sqs'=>['settings'=>['url'=>'http://test']]]],
                'processing-status'=>['endpoint'=>'http://test']
            ])
            ->once();

        $controller = $this->factory->__invoke($this->container, PingController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(PingController::class, $controller);
    }
}
