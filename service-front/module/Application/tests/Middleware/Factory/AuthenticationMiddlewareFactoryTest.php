<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\AuthenticationMiddleware;
use Application\Middleware\Factory\AuthenticationMiddlewareFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AuthenticationMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsAuthenticationMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            SessionUtility::class        => $this->createMock(SessionUtility::class),
            AuthenticationService::class => $this->createMock(AuthenticationService::class),
            UrlHelper::class             => $this->createMock(UrlHelper::class),
        });

        $middleware = (new AuthenticationMiddlewareFactory())($container);

        $this->assertInstanceOf(AuthenticationMiddleware::class, $middleware);
    }
}
