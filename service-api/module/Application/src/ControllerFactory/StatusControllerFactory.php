<?php

namespace Application\ControllerFactory;

use Application\Controller\StatusController;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Laminas\Authentication\AuthenticationService;
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
        $authenticationService = $container->get(AuthenticationService::class);
        $applicationsService = $container->get(ApplicationService::class);
        $processingStatusService = $container->get(ProcessingStatusService::class);

        $controller = new StatusController(
            $authenticationService,
            $applicationsService,
            $processingStatusService
        );

        $controller->setLogger($container->get('Logger'));

        return $controller;
    }
}
