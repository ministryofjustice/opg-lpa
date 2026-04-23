<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\Factory\UserDetailsMiddlewareFactory;
use Application\Middleware\UserDetailsMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsUserDetailsMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            SessionUtility::class        => $this->createMock(SessionUtility::class),
            Details::class               => $this->createMock(Details::class),
            AuthenticationService::class => $this->createMock(AuthenticationService::class),
            'SessionManager'             => $this->createMock(SessionManager::class),
            LoggerInterface::class       => $this->createMock(LoggerInterface::class),
        });

        $middleware = (new UserDetailsMiddlewareFactory())($container);

        $this->assertInstanceOf(UserDetailsMiddleware::class, $middleware);
    }
}
