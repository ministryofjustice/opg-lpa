<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\ConfirmRegistrationHandler;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Authentication\AuthenticationService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ConfirmRegistrationHandlerFactory
{
    public function __invoke(ContainerInterface $container): ConfirmRegistrationHandler
    {
        return new ConfirmRegistrationHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UserService::class),
            $container->get(AuthenticationService::class),
            $container->get(SessionManagerSupport::class),
        );
    }
}
