<?php

namespace Application\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionUtility;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\View\Http\ViewManager;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Model\ViewModel;
use Twig\Environment;

class AccountInfoFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AccountInfo
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var AuthenticationService $authenticationService */
        $authenticationService = $container->get('AuthenticationService');

        /** @var SessionUtility $sessionUtility */
        $sessionUtility = $container->get(SessionUtility::class);

        /** @var ViewManager $viewManager */
        $viewManager = $container->get('ViewManager');

        /** @var Application $application */
        $application = $container->get('Application');

        /** @var LpaApplicationService $lpaApplicationService */
        $lpaApplicationService = $container->get('LpaApplicationService');

        /** @var Environment $viewRenderer */
        $viewRenderer = $container->get('TwigViewRenderer');

        /** @var ViewModel $viewModel */
        $viewModel = $viewManager->getViewModel();

        /** @var RouteMatch $routeMatch */
        $routeMatch = $application->getMvcEvent()->getRouteMatch();

        return new AccountInfo(
            $authenticationService,
            $sessionUtility,
            $viewModel,
            $routeMatch,
            $lpaApplicationService,
            new LocalViewRenderer($viewRenderer)
        );
    }
}
