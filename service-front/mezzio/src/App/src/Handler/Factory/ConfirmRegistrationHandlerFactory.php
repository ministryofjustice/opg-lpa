<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\ConfirmRegistrationHandler;
use Application\Model\Service\User\Details as UserService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ConfirmRegistrationHandlerFactory
{
    public function __invoke(ContainerInterface $container): ConfirmRegistrationHandler
    {
        return new ConfirmRegistrationHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(UserService::class),
        );
    }
}
