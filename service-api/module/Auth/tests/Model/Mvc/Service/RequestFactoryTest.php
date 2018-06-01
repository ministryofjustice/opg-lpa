<?php

namespace AuthTest\Model\Mvc\Service;

use Auth\Model\Http\PhpEnvironment\JsonRequest;
use Auth\Model\Mvc\Service\RequestFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class RequestFactoryTest extends MockeryTestCase
{
    /**
     * @var RequestFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface;
     */
    private $container;

    protected function setUp()
    {
        $this->factory = new RequestFactory();

        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvokeHttpRequest()
    {
        $result = $this->factory->__invoke($this->container, 'UnitTest');

        $this->assertInstanceOf(JsonRequest::class, $result);
    }
}