<?php

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Lpa\Application;
use Application\View\Helper\RouteName;
use Application\View\Helper\RouteNameFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\Router\RouteMatch;

class RouteNameFactoryTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $mvcEvent = Mockery::mock(RouteMatch::class);
        $mvcEvent->shouldReceive('getRouteMatch')->withArgs([])->once()->andReturn($routeMatch);

        $application = Mockery::mock(Application::class);
        $application->shouldReceive('getMvcEvent')->withArgs([])->once()->andReturn($mvcEvent);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['Application'])->once()->andReturn($application);

        $routeNameFactory = new RouteNameFactory();
        $result = $routeNameFactory($container, null, null);

        $this->assertInstanceOf(RouteName::class, $result);
    }
}
