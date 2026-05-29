<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\DateCheckValidHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DateCheckValidHandlerFactory
{
    public function __invoke(ContainerInterface $container): DateCheckValidHandler
    {
        return new DateCheckValidHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
