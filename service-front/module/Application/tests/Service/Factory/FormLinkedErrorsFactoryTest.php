<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Form\Error\FormLinkedErrors;
use Application\Service\Factory\FormLinkedErrorsFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FormLinkedErrorsFactoryTest extends TestCase
{
    public function testFactoryReturnsFormLinkedErrors(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $service = (new FormLinkedErrorsFactory())($container);

        $this->assertInstanceOf(FormLinkedErrors::class, $service);
    }
}
