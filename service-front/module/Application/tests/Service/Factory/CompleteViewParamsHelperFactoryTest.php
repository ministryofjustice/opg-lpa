<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\CompleteViewParamsHelper;
use Application\Service\Factory\CompleteViewParamsHelperFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CompleteViewParamsHelperFactoryTest extends TestCase
{
    public function testFactoryCreatesHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [ContinuationSheets::class, $this->createMock(ContinuationSheets::class)],
            ]);

        $factory = new CompleteViewParamsHelperFactory();
        $helper = $factory($container);

        $this->assertInstanceOf(CompleteViewParamsHelper::class, $helper);
    }
}
