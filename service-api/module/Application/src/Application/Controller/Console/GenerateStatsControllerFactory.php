<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GenerateStatsControllerFactory implements FactoryInterface
{
    /**
     * Create generate stats controller
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return GenerateStatsController
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var ControllerManager $serviceLocator */
        $serviceLocator = $serviceLocator->getServiceLocator();

        /** @var DynamoCronLock $cronLock */
        $cronLock = $serviceLocator->get('DynamoCronLock');
        /** @var Stats $statsService */
        $statsService = $serviceLocator->get('StatsService');

        return new GenerateStatsController($cronLock, $statsService);
    }
}
