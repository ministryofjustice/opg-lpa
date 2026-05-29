<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\ReplacementAttorneyIndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyIndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): ReplacementAttorneyIndexHandler
    {
        return new ReplacementAttorneyIndexHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Metadata::class),
        );
    }
}
