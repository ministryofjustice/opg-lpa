<?php

namespace Application\ControllerFactory;

use Application\Controller\StatsController;
use Application\Model\Service\Stats\Service as StatsService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class StatsControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return StatsController
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array|null $options = null)
    {
        /** @var StatsService $statsService */
        $statsService = $container->get(StatsService::class);

        return new StatsController($statsService);
    }
}
