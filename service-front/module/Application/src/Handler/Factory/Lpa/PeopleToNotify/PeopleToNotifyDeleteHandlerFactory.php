<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
