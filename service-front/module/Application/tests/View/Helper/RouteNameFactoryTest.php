<?php

declare(strict_types=1);

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Session\PersistentSessionDetails;
use Application\View\Helper\RouteName;
use Application\View\Helper\RouteNameFactory;
use Psr\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class RouteNameFactoryTest extends MockeryTestCase
{
    public function testInvoke(): void
    {
        $persistentSession = Mockery::mock(PersistentSessionDetails::class);
        $persistentSession->shouldReceive('getCurrentRoute')
            ->andReturn('')
            ->once();
        $persistentSession->shouldReceive('getPreviousRoute')
            ->andReturn('')
            ->once();

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['PersistentSessionDetails'])
            ->andReturn($persistentSession)->once();

        $routeNameFactory = new RouteNameFactory();
        $result = $routeNameFactory($container, null, null);

        $this->assertInstanceOf(RouteName::class, $result);
    }
}
