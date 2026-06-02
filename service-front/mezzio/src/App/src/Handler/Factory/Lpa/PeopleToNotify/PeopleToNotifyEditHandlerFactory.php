<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\PeopleToNotify;

use App\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PeopleToNotifyEditHandlerFactory
{
    public function __invoke(ContainerInterface $container): PeopleToNotifyEditHandler
    {
        return new PeopleToNotifyEditHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
