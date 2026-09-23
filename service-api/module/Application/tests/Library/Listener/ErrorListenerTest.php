<?php

namespace ApplicationTest;

use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\ApiProblem\ApiProblemException;
use Psr\Log\LoggerInterface;
use Application\Library\Listener\ErrorListener;
use Exception;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ErrorListenerTest extends TestCase
{
    private LoggerInterface&MockInterface $logger;
    private ErrorListener $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->listener = new ErrorListener($this->logger);
    }

    public function testNonExceptionIgnored(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event->expects($this->once())
            ->method('getParam')
            ->with('exception')
            ->willReturn(null);

        $response = $this->listener->onError($event);

        $this->assertNull($response);
    }

    public function testApiProblemConverted(): void
    {
        $event = $this->createMock(MvcEvent::class);

        $event->setApplication(Mockery::mock(Application::class));

        $event->expects($this->once())
            ->method('getParam')
            ->with('exception')
            ->willReturn(new ApiProblemException('Information not found', 404));

        $event->expects($this->once())
            ->method('stopPropagation');

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof ApiProblemResponse
                    && $response->getStatusCode() === 404
                    && str_contains($response->getContent(), '"detail":"Information not found"');
            }));

        $this->listener->onError($event);
    }

    public function testOtherExceptionsLoggedAndConverted(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event->expects($this->once())
            ->method('getParam')
            ->with('exception')
            ->willReturn(new Exception('Something unknown went wrong'));

        $event->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response());

        $event->expects($this->once())
            ->method('stopPropagation');

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof ApiProblemResponse
                    && $response->getStatusCode() === 500
                    && str_contains($response->getContent(), '"detail":"An unexpected error occurred"');
            }));

        $this->logger->shouldReceive('error')
            ->once()
            ->with('Something unknown went wrong', Mockery::on(function ($context) {
                return $context['class'] === Exception::class
                    && isset($context['file'])
                    && isset($context['line'])
                    && isset($context['stackTrace']);
            }));

        $this->listener->onError($event);
    }

    public function testExistingResponseKept(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event->expects($this->once())
            ->method('getParam')
            ->with('exception')
            ->willReturn(new Exception('Something unknown went wrong'));

        $existingResponse = new Response();
        $existingResponse->setContent('Existing response content');
        $event->expects($this->once())
            ->method('getResponse')
            ->willReturn($existingResponse);

        $event->expects($this->never())
            ->method('stopPropagation');

        $event->expects($this->never())
            ->method('setResponse');

        $this->logger->shouldReceive('error')
            ->once()
            ->with('Something unknown went wrong', Mockery::on(function ($context) {
                return $context['class'] === Exception::class
                    && isset($context['file'])
                    && isset($context['line'])
                    && isset($context['stackTrace']);
            }));

        $this->listener->onError($event);
    }
}
