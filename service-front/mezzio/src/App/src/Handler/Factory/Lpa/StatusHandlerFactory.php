<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\StatusHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class StatusHandlerFactory
{
    public function __invoke(ContainerInterface $container): StatusHandler
    {
        return new StatusHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
            $container->get('config'),
        );
    }
}
