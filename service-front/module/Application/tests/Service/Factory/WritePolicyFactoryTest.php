<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\WritePolicyFactory;
use Application\Model\Service\Session\WritePolicy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class WritePolicyFactoryTest extends TestCase
{
    public function testFactoryReturnsWritePolicy(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $policy = (new WritePolicyFactory())($container);

        $this->assertInstanceOf(WritePolicy::class, $policy);
    }

    public function testWritePolicyAllowsWriteByDefault(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $policy = (new WritePolicyFactory())($container);

        // Without the MVC Request (Mezzio context), falls back to $_SERVER check.
        // In a test environment HTTP_X_SESSIONREADONLY is absent, so writes are allowed.
        $this->assertTrue($policy->allowsWrite());
    }
}
