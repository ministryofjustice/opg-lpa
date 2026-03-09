<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\CreateLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CreateLpaHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CreateLpaHandler
    {
        return new CreateLpaHandler(
            $container->get(LpaApplicationService::class),
            $container->get('ControllerPluginManager')->get(FlashMessenger::class),
            $container->get(SessionManagerSupport::class),
        );
    }
}
