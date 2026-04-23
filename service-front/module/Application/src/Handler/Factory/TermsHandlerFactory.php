<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\TermsHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TermsHandlerFactory
{
    public function __invoke(ContainerInterface $container): TermsHandler
    {
        return new TermsHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
