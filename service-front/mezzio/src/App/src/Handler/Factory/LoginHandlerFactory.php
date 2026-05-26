<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\LoginHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class LoginHandlerFactory
{
    public function __invoke(ContainerInterface $container): LoginHandler
    {
        return new LoginHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
        );
    }
}
