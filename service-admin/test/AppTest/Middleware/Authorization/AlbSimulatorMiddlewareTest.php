<?php

declare(strict_types=1);

namespace AppTest\Middleware\Authorization;

use App\Middleware\Authorization\AlbSimulatorMiddleware;
use App\Service\Cognito\Client as CognitoClient;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class AlbSimulatorMiddlewareTest extends TestCase
{
    private CognitoClient|MockObject $cognitoClient;
    private RequestHandlerInterface|MockObject $handler;
    private AlbSimulatorMiddleware $middleware;

    protected function setUp(): void
    {
        $this->cognitoClient = $this->createMock(CognitoClient::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new AlbSimulatorMiddleware($this->cognitoClient, 'dev@example.com');
    }

    public function testPassesThroughWhenAlbHeaderAlreadyPresent(): void
    {
        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', 'existing-token');

        $this->cognitoClient->expects($this->never())
            ->method('fetchTestToken');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) use ($request) {
                self::assertSame($request, $handledRequest);
                self::assertSame('existing-token', $handledRequest->getHeaderLine('X-Amzn-Oidc-Data'));
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testInjectsTokenWhenNoHeader(): void
    {
        $request = new ServerRequest();

        $this->cognitoClient->expects($this->once())
            ->method('fetchTestToken')
            ->with('dev@example.com')
            ->willReturn('test-token');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) {
                self::assertSame('test-token', $handledRequest->getHeaderLine('X-Amzn-Oidc-Data'));
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testPassesThroughWhenSignedOutWithoutSigninParam(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = (new ServerRequest())->withAttribute(SessionInterface::class, $session);

        $session->expects($this->once())
            ->method('get')
            ->with('signed_out', false)
            ->willReturn(true);
        $session->expects($this->never())
            ->method('unset');

        $this->cognitoClient->expects($this->never())
            ->method('fetchTestToken');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) {
                self::assertSame('', $handledRequest->getHeaderLine('X-Amzn-Oidc-Data'));
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testClearsSignedOutFlagAndInjectsTokenWhenSigninParamPresent(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = (new ServerRequest())
            ->withAttribute(SessionInterface::class, $session)
            ->withQueryParams(['signin' => '1']);

        $session->expects($this->once())
            ->method('get')
            ->with('signed_out', false)
            ->willReturn(true);
        $session->expects($this->once())
            ->method('unset')
            ->with('signed_out');

        $this->cognitoClient->expects($this->once())
            ->method('fetchTestToken')
            ->with('dev@example.com')
            ->willReturn('refreshed-token');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) {
                self::assertSame('refreshed-token', $handledRequest->getHeaderLine('X-Amzn-Oidc-Data'));
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }
}
