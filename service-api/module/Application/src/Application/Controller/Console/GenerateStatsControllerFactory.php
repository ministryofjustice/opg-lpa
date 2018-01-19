<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class GenerateStatsControllerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DynamoCronLock $cronLock */
        $cronLock = $container->get('DynamoCronLock');
        /** @var Stats $statsService */
        $statsService = $container->get('StatsService');

        return new GenerateStatsController($cronLock, $statsService);
    }
}
