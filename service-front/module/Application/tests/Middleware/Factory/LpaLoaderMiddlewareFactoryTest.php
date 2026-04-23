<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Helper\MvcUrlHelper;
use Application\Middleware\Factory\LpaLoaderMiddlewareFactory;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LpaLoaderMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsLpaLoaderMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            LpaApplicationService::class => $this->createMock(LpaApplicationService::class),
            MvcUrlHelper::class          => $this->createMock(MvcUrlHelper::class),
        });

        $middleware = (new LpaLoaderMiddlewareFactory())($container);

        $this->assertInstanceOf(LpaLoaderMiddleware::class, $middleware);
    }
}
