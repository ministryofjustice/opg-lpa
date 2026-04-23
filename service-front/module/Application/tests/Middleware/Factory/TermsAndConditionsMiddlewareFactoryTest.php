<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\Factory\TermsAndConditionsMiddlewareFactory;
use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TermsAndConditionsMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsTermsAndConditionsMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'                     => ['terms' => ['lastUpdated' => '2020-01-01']],
            SessionUtility::class        => $this->createMock(SessionUtility::class),
            AuthenticationService::class => $this->createMock(AuthenticationService::class),
            UrlHelper::class             => $this->createMock(UrlHelper::class),
        });

        $middleware = (new TermsAndConditionsMiddlewareFactory())($container);

        $this->assertInstanceOf(TermsAndConditionsMiddleware::class, $middleware);
    }
}
