<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\PeopleToNotify;

use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class PeopleToNotifyDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): PeopleToNotifyDeleteHandler
    {
        return new PeopleToNotifyDeleteHandler(
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
