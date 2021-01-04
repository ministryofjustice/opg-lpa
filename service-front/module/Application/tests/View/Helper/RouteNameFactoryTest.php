<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\RouteName;
use Application\View\Helper\RouteNameFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class RouteNameFactoryTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['PersistentSessionDetails'])
            ->andReturn(Mockery::mock(ContainerInterface::class))->once();

        $routeNameFactory = new RouteNameFactory();
        $result = $routeNameFactory($container, null, null);

        $this->assertInstanceOf(RouteName::class, $result);
    }
}
