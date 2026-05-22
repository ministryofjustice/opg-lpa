<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\LpaTypeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Laminas\Router\RouteStackInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class LpaTypeHandlerFactory
{
    public function __invoke(ContainerInterface $container): LpaTypeHandler
    {
        return new LpaTypeHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            new MvcUrlHelper($container->get(RouteStackInterface::class)),
        );
    }
}
