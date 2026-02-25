<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DeleteLpaHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DeleteLpaHandler
    {
        return new DeleteLpaHandler(
            $container->get(LpaApplicationService::class),
            $container->get('ControllerPluginManager')->get(FlashMessenger::class),
        );
    }
}
