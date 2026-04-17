<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\Download;

use Application\Handler\Lpa\Download\DownloadHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DownloadHandlerFactory
{
    public function __invoke(ContainerInterface $container): DownloadHandler
    {
        return new DownloadHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(LoggerInterface::class),
        );
    }
}
