<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\TypeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Laminas\Router\RouteStackInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TypeHandlerFactory
{
    public function __invoke(ContainerInterface $container): TypeHandler
    {
        return new TypeHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            new MvcUrlHelper($container->get(RouteStackInterface::class)),
        );
    }
}
