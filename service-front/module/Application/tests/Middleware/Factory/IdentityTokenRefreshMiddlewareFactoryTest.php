<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\Factory\IdentityTokenRefreshMiddlewareFactory;
use Application\Middleware\IdentityTokenRefreshMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class IdentityTokenRefreshMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsIdentityTokenRefreshMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            AuthenticationService::class => $this->createMock(AuthenticationService::class),
            UserService::class           => $this->createMock(UserService::class),
            SessionUtility::class        => $this->createMock(SessionUtility::class),
        });

        $middleware = (new IdentityTokenRefreshMiddlewareFactory())($container);

        $this->assertInstanceOf(IdentityTokenRefreshMiddleware::class, $middleware);
    }
}
