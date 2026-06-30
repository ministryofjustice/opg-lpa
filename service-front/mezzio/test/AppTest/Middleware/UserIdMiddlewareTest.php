<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Authentication\AuthenticationService;
use App\Middleware\UserIdMiddleware;
use App\Model\Service\Authentication\Identity\User as Identity;
use DateTime;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

class UserIdMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authService;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        return $handler;
    }

    private function makeIdentity(string $userId = 'user-abc-123'): Identity
    {
        return new Identity(
            $userId,
            'test-token',
            3600,
            new DateTime(),
        );
    }

    public function testPushesProcessorWithUserIdWhenAuthenticatedAndLoggerIsMonolog(): void
    {
        $identity = $this->makeIdentity('user-123');
        $this->authService->method('getIdentity')->willReturn($identity);

        $pushedProcessor = null;
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('pushProcessor')
            ->with($this->isType('callable'))
            ->willReturnCallback(function (callable $processor) use (&$pushedProcessor, $logger) {
                $pushedProcessor = $processor;
                return $logger;
            });

        $middleware = new UserIdMiddleware($logger, $this->authService);
        $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertNotNull($pushedProcessor);

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: [],
            extra: [],
        );

        $processed = ($pushedProcessor)($record);
        $this->assertSame('user-123', $processed->extra['user_id']);
    }

    public function testDoesNotPushProcessorWhenNoIdentity(): void
    {
        $this->authService->method('getIdentity')->willReturn(null);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('pushProcessor');

        $middleware = new UserIdMiddleware($logger, $this->authService);
        $response = $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertNotNull($response);
    }

    public function testDoesNotPushProcessorWhenLoggerIsNotMonolog(): void
    {
        $identity = $this->makeIdentity();
        $this->authService->method('getIdentity')->willReturn($identity);

        $logger = new NullLogger();
        $middleware = new UserIdMiddleware($logger, $this->authService);

        // Should not throw; NullLogger has no pushProcessor method
        $response = $middleware->process(new ServerRequest(), $this->makeHandler());
        $this->assertNotNull($response);
    }

    public function testProcessorPreservesExistingExtraFields(): void
    {
        $identity = $this->makeIdentity('user-456');
        $this->authService->method('getIdentity')->willReturn($identity);

        $pushedProcessor = null;
        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$pushedProcessor, $logger) {
                $pushedProcessor = $processor;
                return $logger;
            });

        $middleware = new UserIdMiddleware($logger, $this->authService);
        $middleware->process(new ServerRequest(), $this->makeHandler());

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: [],
            extra: ['trace_id' => 'abc-trace', 'request_path' => '/some/path'],
        );

        $processed = ($pushedProcessor)($record);
        $this->assertSame('user-456', $processed->extra['user_id']);
        $this->assertSame('abc-trace', $processed->extra['trace_id']);
        $this->assertSame('/some/path', $processed->extra['request_path']);
    }
}
