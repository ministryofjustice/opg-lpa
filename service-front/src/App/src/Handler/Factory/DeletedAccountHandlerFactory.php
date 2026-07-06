<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\DeletedAccountHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DeletedAccountHandlerFactory
{
    public function __invoke(ContainerInterface $container): DeletedAccountHandler
    {
        return new DeletedAccountHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
