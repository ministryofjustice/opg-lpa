<?php

declare(strict_types=1);

namespace Application\Form\Factory;

use Application\Form\Element\CsrfBuilder;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

/**
 * Creates CsrfBuilder, injecting the ServiceManager directly.
 *
 * CsrfBuilder acts as a service-locator factory for CSRF form elements — it
 * needs the full ServiceManager so it can lazily resolve config, Logger, and
 * SessionUtility when building each Csrf element instance. In a laminas-servicemanager
 * Mezzio container the ContainerInterface IS the ServiceManager, so we cast it.
 */
class CsrfBuilderFactory
{
    public function __invoke(ContainerInterface $container): CsrfBuilder
    {
        /** @var ServiceManager $container */
        return new CsrfBuilder($container);
    }
}
