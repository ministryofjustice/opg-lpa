<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PeopleToNotifyConfirmDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): PeopleToNotifyConfirmDeleteHandler
    {
        return new PeopleToNotifyConfirmDeleteHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
