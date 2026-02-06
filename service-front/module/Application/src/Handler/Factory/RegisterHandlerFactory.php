<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\RegisterHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RegisterHandlerFactory
{
    public function __invoke(ContainerInterface $container): RegisterHandler
    {
        return new RegisterHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
            $container->get(LoggerInterface::class),
        );
    }
}
