<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\SessionMiddlewareFactory;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SessionMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsSessionMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $middleware = (new SessionMiddlewareFactory())($container);

        $this->assertInstanceOf(SessionMiddleware::class, $middleware);
    }
}
