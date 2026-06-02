<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\DonorAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DonorAddHandlerFactory
{
    public function __invoke(ContainerInterface $container): DonorAddHandler
    {
        return new DonorAddHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(ActorReuseDetailsService::class),
        );
    }
}
