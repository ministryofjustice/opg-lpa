<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\ChangeEmailAddressHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ChangeEmailAddressHandlerFactory
{
    public function __invoke(ContainerInterface $container): ChangeEmailAddressHandler
    {
        return new ChangeEmailAddressHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
        );
    }
}
