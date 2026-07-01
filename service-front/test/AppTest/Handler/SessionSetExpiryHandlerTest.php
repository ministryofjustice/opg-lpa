<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SessionSetExpiryHandler;
use App\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionSetExpiryHandlerTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private SessionSetExpiryHandler $handler;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->handler = new SessionSetExpiryHandler($this->authenticationService);
    }

    private function createRequest(string $method, string $body = ''): ServerRequest
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($body);
        $stream->rewind();

        return (new ServerRequest())
            ->withMethod($method)
            ->withBody($stream);
    }

    public function testNonPostRequestReturns405(): void
    {
        $request = $this->createRequest('GET');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Method not allowed', (string) $response->getBody());
    }

    public function testEmptyBodyReturns400(): void
    {
        $request = $this->createRequest('POST', '');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Malformed request', (string) $response->getBody());
    }

    public function testMissingExpireInSecondsFieldReturns400(): void
    {
        $request = $this->createRequest('POST', json_encode(['foo' => 'bar']));

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInvalidJsonReturns400(): void
    {
        $request = $this->createRequest('POST', 'not-json');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testValidRequestCallsSetSessionExpiryAndReturnsRemainingSeconds(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('setSessionExpiry')
            ->with(300)
            ->willReturn(295);

        $request = $this->createRequest('POST', '{"expireInSeconds": 300}');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(295, $body['remainingSeconds']);
    }
}
