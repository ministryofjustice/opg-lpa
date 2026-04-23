<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\Factory\SessionBootstrapMiddlewareFactory;
use Application\Middleware\SessionBootstrapMiddleware;
use Application\Model\Service\Session\NativeSessionConfig;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SessionBootstrapMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsSessionBootstrapMiddleware(): void
    {
        // NativeSessionConfig is final — construct a real instance.
        $nativeSessionConfig = new NativeSessionConfig([], $this->createMock(SaveHandlerInterface::class));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            NativeSessionConfig::class   => $nativeSessionConfig,
            'SessionManager'             => $this->createMock(SessionManager::class),
            SessionManagerSupport::class => $this->createMock(SessionManagerSupport::class),
        });

        $middleware = (new SessionBootstrapMiddlewareFactory())($container);

        $this->assertInstanceOf(SessionBootstrapMiddleware::class, $middleware);
    }
}
