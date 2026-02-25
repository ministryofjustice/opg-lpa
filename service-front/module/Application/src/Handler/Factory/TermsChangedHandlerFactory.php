<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\TermsChangedHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TermsChangedHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): TermsChangedHandler
    {
        return new TermsChangedHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
