<?php

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Lpa\Application;
use Application\Model\Service\Session\SessionManager;
use Application\View\Helper\RouteName;
use Application\View\Helper\RouteNameFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Laminas\Router\RouteMatch;

class RouteNameFactoryTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $mvcEvent = Mockery::mock(RouteMatch::class);
        $mvcEvent->shouldReceive('getRouteMatch')->withArgs([])
            ->andReturn($routeMatch)->once();

        $application = Mockery::mock(Application::class);
        $application->shouldReceive('getMvcEvent')->withArgs([])
            ->andReturn($mvcEvent)->once();

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['Application'])
            ->andReturn($application)->once();
        $container->shouldReceive('get')->withArgs(['SessionManager'])
            ->andReturn(Mockery::mock(SessionManager::class))->once();

        $routeNameFactory = new RouteNameFactory();
        $result = $routeNameFactory($container, null, null);

        $this->assertInstanceOf(RouteName::class, $result);
    }
}
