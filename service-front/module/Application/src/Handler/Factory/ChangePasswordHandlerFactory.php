<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\ChangePasswordHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ChangePasswordHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ChangePasswordHandler
    {
        return new ChangePasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
            $container->get(FlashMessenger::class),
        );
    }
}
