<?php

namespace Application\ControllerFactory;

use Application\Controller\StatusController;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Lmc\Rbac\Mvc\Service\AuthorizationService;
use Psr\Container\ContainerInterface;

class StatusControllerFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $_requestedName
     * @param array|null $_options
     * @return StatusController
     */
    public function __invoke(ContainerInterface $container, string $_requestedName, array|null $_options = null)
    {
        $authorizationService = $container->get(AuthorizationService::class);
        $applicationsService = $container->get(ApplicationService::class);
        $processingStatusService = $container->get(ProcessingStatusService::class);

        $controller = new StatusController(
            $authorizationService,
            $applicationsService,
            $processingStatusService
        );

        $controller->setLogger($container->get('Logger'));

        return $controller;
    }
}
