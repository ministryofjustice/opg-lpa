<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\DeleteAccountHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DeleteAccountHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DeleteAccountHandler
    {
        return new DeleteAccountHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(AuthenticationService::class),
        );
    }
}
