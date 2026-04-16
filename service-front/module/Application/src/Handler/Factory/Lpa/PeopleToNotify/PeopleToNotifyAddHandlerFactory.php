<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PeopleToNotifyAddHandlerFactory
{
    public function __invoke(ContainerInterface $container): PeopleToNotifyAddHandler
    {
        return new PeopleToNotifyAddHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Metadata::class),
            $container->get(ActorReuseDetailsService::class),
        );
    }
}
