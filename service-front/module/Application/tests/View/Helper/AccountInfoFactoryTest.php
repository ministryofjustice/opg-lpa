<?php

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use Mockery;
use Application\View\Helper\AccountInfo;
use Application\View\Helper\AccountInfoFactory;
use Interop\Container\ContainerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RouteMatch;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

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
        $viewManager = Mockery::mock(ViewManager::class);
        $viewManager->shouldReceive('getViewModel')->once()->andReturn($viewModel);

        $authenticationService = Mockery::mock(AuthenticationService::class);
        $userDetailsSession = Mockery::mock(Container::class);
        $twigViewRender = Mockery::mock(\Twig_Environment::class);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['TwigViewRenderer'])->once()->andReturn($twigViewRender);
        $container->shouldReceive('get')->withArgs(['LpaApplicationService'])->once()->andReturn($application);
        $container->shouldReceive('get')->withArgs(['Application'])->once()->andReturn($application);
        $container->shouldReceive('get')->withArgs(['ViewManager'])->once()->andReturn($viewManager);
        $container->shouldReceive('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);
        $container->shouldReceive('get')->withArgs(['AuthenticationService'])->once()
                    ->andReturn($authenticationService);

        $accountInfoFactory = new AccountInfoFactory();
        $result = $accountInfoFactory($container, null, null);

        $this->assertInstanceOf(AccountInfo::class, $result);
    }
}
