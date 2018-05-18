<?php

namespace Application\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Interop\Container\ContainerInterface;
use Opg\Lpa\DataModel\User\User;
use Zend\Mvc\Application;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Twig_Environment;

class AccountInfoFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AccountInfo
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var AuthenticationService $authenticationService */
        $authenticationService = $container->get('AuthenticationService');
        /** @var $userSessionDetails Container */
        $userDetailsSession = $container->get('UserDetailsSession');
        /** @var ViewManager $viewManager */
        $viewManager = $container->get('ViewManager');
        /** @var Application $application */
        $application = $container->get('Application');
        /** @var LpaApplicationService $lpaApplicationService */
        $lpaApplicationService = $container->get('LpaApplicationService');
        /** @var Twig_Environment $viewRenderer */
        $viewRenderer = $container->get('TwigViewRenderer');

        /** @var ViewModel $viewModel */
        $viewModel = $viewManager->getViewModel();
        /** @var RouteMatch $routeMatch */
        $routeMatch = $application->getMvcEvent()->getRouteMatch();

        return new AccountInfo($authenticationService, $userDetailsSession, $viewModel, $routeMatch, $lpaApplicationService, $viewRenderer);
    }
}
