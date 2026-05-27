<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\CsrfValidationMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfValidationMiddlewareTest extends TestCase
{
    private CsrfGuardInterface&MockObject $guard;
    private CsrfValidationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->guard = $this->createMock(CsrfGuardInterface::class);
        $this->middleware = new CsrfValidationMiddleware();
    }

    private function makeRequestWithGuard(string $method = 'GET', ?array $body = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard);

        if ($body !== null) {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    private function handlerReturning(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        return $handler;
    }

    public function testGetRequestGeneratesTokenAndPassesThrough(): void
    {
        $this->guard->expects($this->never())->method('validateToken');
        $this->guard->expects($this->once())->method('generateToken')->willReturn('fresh-token');

        $expected = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use ($expected): ResponseInterface {
                $this->assertEquals('fresh-token', $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE));
                return $expected;
            });

        $result = $this->middleware->process($this->makeRequestWithGuard('GET'), $handler);

        $this->assertSame($expected, $result);
    }

    public function testPostWithValidTokenPassesThrough(): void
    {
        $this->guard->expects($this->once())->method('validateToken')->with('valid-token')->willReturn(true);
        $this->guard->expects($this->once())->method('generateToken')->willReturn('fresh-token');

        $expected = new EmptyResponse();

        $result = $this->middleware->process(
            $this->makeRequestWithGuard('POST', ['__csrf' => 'valid-token']),
            $this->handlerReturning($expected)
        );

        $this->assertSame($expected, $result);
    }

    public function testPostWithInvalidTokenRedirectsToSamePath(): void
    {
        $this->guard->expects($this->once())->method('validateToken')->with('bad-token')->willReturn(false);
        $this->guard->expects($this->never())->method('generateToken');

        $request = (new ServerRequest(uri: 'https://example.com/lpa/123/type'))
            ->withMethod('POST')
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard)
            ->withParsedBody(['__csrf' => 'bad-token']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/lpa/123/type', $result->getHeaderLine('Location'));
    }

    public function testPostWithMissingCsrfFieldRedirects(): void
    {
        $this->guard->expects($this->once())->method('validateToken')->with('')->willReturn(false);

        $result = $this->middleware->process(
            $this->makeRequestWithGuard('POST', ['other' => 'data']),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testTokenAttributeNameIsCorrect(): void
    {
        $this->assertEquals('csrfToken', CsrfValidationMiddleware::TOKEN_ATTRIBUTE);
    }
}
