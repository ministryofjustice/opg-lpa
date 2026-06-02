<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\Download;

use App\Handler\Lpa\Download\DownloadFileHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DownloadFileHandlerFactory
{
    public function __invoke(ContainerInterface $container): DownloadFileHandler
    {
        return new DownloadFileHandler(
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(LoggerInterface::class),
        );
    }
}
