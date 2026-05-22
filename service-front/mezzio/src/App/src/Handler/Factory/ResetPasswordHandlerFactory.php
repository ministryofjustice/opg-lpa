<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\ResetPasswordHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ResetPasswordHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResetPasswordHandler
    {
        return new ResetPasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
        );
    }
}
