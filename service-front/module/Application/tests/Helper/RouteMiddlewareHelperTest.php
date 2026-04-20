<?php

declare(strict_types=1);

namespace ApplicationTest\Helper;

use Application\Helper\RouteMiddlewareHelper;
use Application\Middleware\AuthenticationMiddleware;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RouteMatchMiddleware;
use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Middleware\UserDetailsMiddleware;
use Laminas\Mvc\Middleware\PipeSpec;
use PHPUnit\Framework\TestCase;

class RouteMiddlewareHelperTest extends TestCase
{
    private const FULL_STACK = [
        RouteMatchMiddleware::class,
        AuthenticationMiddleware::class,
        UserDetailsMiddleware::class,
        TermsAndConditionsMiddleware::class,
        LpaLoaderMiddleware::class,
    ];

    public function testReturnsAPipeSpec(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', []);

        $this->assertInstanceOf(PipeSpec::class, $result);
    }

    public function testFullStackWithNoIgnoredMiddleware(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', []);

        $expected = array_merge(self::FULL_STACK, ['SomeHandler']);

        $this->assertSame(array_values($expected), $result->getSpec());
    }

    public function testHandlerIsAlwaysAppendedLast(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('MyHandler', []);

        $spec = $result->getSpec();
        $this->assertSame('MyHandler', end($spec));
    }

    public function testSingleMiddlewareCanBeIgnored(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', [AuthenticationMiddleware::class]);

        $spec = $result->getSpec();
        $this->assertNotContains(AuthenticationMiddleware::class, $spec);
        $this->assertContains(RouteMatchMiddleware::class, $spec);
        $this->assertContains(UserDetailsMiddleware::class, $spec);
        $this->assertContains(TermsAndConditionsMiddleware::class, $spec);
        $this->assertContains(LpaLoaderMiddleware::class, $spec);
        $this->assertContains('SomeHandler', $spec);
    }

    public function testMultipleMiddlewaresCanBeIgnored(): void
    {
        $ignore = [AuthenticationMiddleware::class, LpaLoaderMiddleware::class];

        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', $ignore);

        $spec = $result->getSpec();
        $this->assertNotContains(AuthenticationMiddleware::class, $spec);
        $this->assertNotContains(LpaLoaderMiddleware::class, $spec);
        $this->assertContains(RouteMatchMiddleware::class, $spec);
        $this->assertContains(UserDetailsMiddleware::class, $spec);
        $this->assertContains(TermsAndConditionsMiddleware::class, $spec);
        $this->assertContains('SomeHandler', $spec);
    }

    public function testAllMiddlewareCanBeIgnored(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', self::FULL_STACK);

        $this->assertSame(['SomeHandler'], $result->getSpec());
    }

    public function testIgnoringNonExistentMiddlewareHasNoEffect(): void
    {
        $result = RouteMiddlewareHelper::addMiddleware('SomeHandler', ['NonExistentMiddleware::class']);

        $expected = array_merge(self::FULL_STACK, ['SomeHandler']);

        $this->assertSame(array_values($expected), $result->getSpec());
    }
}
