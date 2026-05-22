<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\PrimaryAttorney;

use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyEditHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrimaryAttorneyEditHandler
    {
        return new PrimaryAttorneyEditHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
