<?php
namespace Application\ControllerFactory;

use Application\Controller\General\PingController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;


class PingControllerFactory implements FactoryInterface
{
    /**
     * Create a PingController
     *
     * @param  ContainerInterface $container
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = NULL)
    {
        $controller = new PingController();
        $controller->setStatusService($container->get('SiteStatus'));
        $controller->setConfig($container->get('config'));
        return $controller;
    }
}
