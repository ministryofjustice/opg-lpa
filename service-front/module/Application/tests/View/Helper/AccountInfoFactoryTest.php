<?php
/**
 * Created by PhpStorm.
 * User: seemamenon
 * Date: 28/01/2019
 * Time: 14:08
 */

namespace ApplicationTest\View\Helper;

use Mockery;
use Application\View\Helper\AccountInfo;
use Application\View\Helper\AccountInfoFactory;
use Interop\Container\ContainerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\View\Helper\ViewModel;

class AccountInfoFactoryTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $routeMatch = Mockery::mock(RouteMatch::class);

        $mvcEvent = Mockery::mock(RouteMatch::class);
        $mvcEvent->shouldReceive('getRouteMatch')->withArgs([])->once()->andReturn($routeMatch);

        $application = Mockery::mock(Application::class);
        $application->shouldReceive('getMvcEvent')->withArgs([])->once()->andReturn($mvcEvent);

        $viewModel = Mockery::mock(ViewModel::class);

        $viewManager = Mockery::mock(ViewModel::class);
        $viewManager->shouldReceive('getViewModel')->withArgs([])->once()->andReturn($viewModel);


        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['TwigViewRenderer'])->once()->andReturn($application);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['LpaApplicationService'])->once()->andReturn($application);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['Application'])->once()->andReturn($application);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['ViewManager'])->once()->andReturn($application);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['UserDetailsSession'])->once()->andReturn($application);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['AuthenticationService'])->once()->andReturn($application);


        $accountInfoFactory = new AccountInfoFactory();
        $result = $accountInfoFactory($container, null, null);

        $this->assertInstanceOf(AccountInfo::class, $result);
    }
}