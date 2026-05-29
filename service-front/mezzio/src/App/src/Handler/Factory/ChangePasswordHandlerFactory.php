<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\ChangePasswordHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ChangePasswordHandlerFactory
{
    public function __invoke(ContainerInterface $container): ChangePasswordHandler
    {
        return new ChangePasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
        );
    }
}
