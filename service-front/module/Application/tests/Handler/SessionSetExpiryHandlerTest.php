<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\SessionSetExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionSetExpiryHandlerTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private SessionSetExpiryHandler $handler;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);

        $this->handler = new SessionSetExpiryHandler(
            $this->authenticationService,
        );
    }

    private function createPostRequest(string $body): ServerRequest
    {
        $stream = (new StreamFactory())->createStream($body);

        return (new ServerRequest())
            ->withMethod('POST')
            ->withBody($stream);
    }

    public function testReturns405ForNonPostRequest(): void
    {
        $request = (new ServerRequest())->withMethod('GET');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Method not allowed', (string) $response->getBody());
    }

    public function testReturns400ForEmptyBody(): void
    {
        $request = $this->createPostRequest('');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Malformed request', (string) $response->getBody());
    }

    public function testReturns400ForMissingExpireInSeconds(): void
    {
        $request = $this->createPostRequest(json_encode(['foo' => 'bar']));

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testReturns400ForInvalidJson(): void
    {
        $request = $this->createPostRequest('not-json');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSetsSessionExpiryAndReturnsRemainingSeconds(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('setSessionExpiry')
            ->with(300)
            ->willReturn(295);

        $request = $this->createPostRequest(json_encode(['expireInSeconds' => 300]));

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(295, $body['remainingSeconds']);
    }
}
