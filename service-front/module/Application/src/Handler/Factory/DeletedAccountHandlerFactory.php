<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\DeletedAccountHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DeletedAccountHandlerFactory
{
    public function __invoke(ContainerInterface $container): DeletedAccountHandler
    {
        return new DeletedAccountHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(AuthenticationService::class),
            $container->get(SessionManagerSupport::class),
        );
    }
}
