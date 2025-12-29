<?php

declare(strict_types=1);

namespace ApplicationTest\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use Application\Model\Service\Session\SessionUtility;
use Mockery;
use Application\View\Helper\AccountInfo;
use Application\View\Helper\AccountInfoFactory;
use Psr\Container\ContainerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Laminas\Mvc\View\Http\ViewManager;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use Twig\Environment as TwigEnvironment;

final class AccountInfoFactoryTest extends MockeryTestCase
{
    public function testInvoke(): void
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
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $twigViewRender = Mockery::mock(TwigEnvironment::class);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['TwigViewRenderer'])->once()->andReturn($twigViewRender);
        $container->shouldReceive('get')->withArgs(['LpaApplicationService'])->once()->andReturn($application);
        $container->shouldReceive('get')->withArgs(['Application'])->once()->andReturn($application);
        $container->shouldReceive('get')->withArgs(['ViewManager'])->once()->andReturn($viewManager);
        $container->shouldReceive('get')->withArgs([SessionUtility::class])->once()->andReturn($sessionUtility);
        $container->shouldReceive('get')->withArgs(['AuthenticationService'])->once()
                    ->andReturn($authenticationService);

        $accountInfoFactory = new AccountInfoFactory();
        $result = $accountInfoFactory($container, null, null);

        $this->assertInstanceOf(AccountInfo::class, $result);
    }
}
