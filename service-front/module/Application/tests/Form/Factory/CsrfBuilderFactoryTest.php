<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Factory;

use Application\Form\Element\CsrfBuilder;
use Application\Form\Factory\CsrfBuilderFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class CsrfBuilderFactoryTest extends TestCase
{
    public function testFactoryReturnsCsrfBuilder(): void
    {
        // CsrfBuilder requires a ServiceManager (not just ContainerInterface)
        // because it acts as a service-locator factory internally.
        $serviceManager = $this->createMock(ServiceManager::class);

        $builder = (new CsrfBuilderFactory())($serviceManager);

        $this->assertInstanceOf(CsrfBuilder::class, $builder);
    }
}
