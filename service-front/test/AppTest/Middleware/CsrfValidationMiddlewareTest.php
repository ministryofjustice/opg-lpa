<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\CsrfValidationMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfValidationMiddlewareTest extends TestCase
{
    private CsrfGuardInterface&MockObject $guard;
    private SessionInterface&MockObject $session;
    private CsrfValidationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->guard   = $this->createMock(CsrfGuardInterface::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->middleware = new CsrfValidationMiddleware();
    }

    private function makeRequest(
        string $method = 'GET',
        ?array $body = null,
        bool $withSession = true,
    ): ServerRequest {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard);

        if ($withSession) {
            $request = $request->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
        }

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

    public function testGetWithNoExistingTokenGeneratesNewToken(): void
    {
        // No existing token in session
        $this->session->method('has')->with('__csrf')->willReturn(false);
        $this->guard->expects($this->once())->method('generateToken')->willReturn('fresh-token');

        $expected = new EmptyResponse();
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use ($expected): ResponseInterface {
                $this->assertEquals('fresh-token', $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE));
                return $expected;
            });

        $result = $this->middleware->process($this->makeRequest('GET'), $handler);

        $this->assertSame($expected, $result);
    }

    public function testGetWithExistingTokenReusesItWithoutRegenerating(): void
    {
        // Existing token in session — must be reused, guard must NOT be called
        $this->session->method('has')->with('__csrf')->willReturn(true);
        $this->session->method('get')->with('__csrf')->willReturn('existing-token');
        $this->guard->expects($this->never())->method('generateToken');

        $expected = new EmptyResponse();
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use ($expected): ResponseInterface {
                $this->assertEquals('existing-token', $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE));
                return $expected;
            });

        $result = $this->middleware->process($this->makeRequest('GET'), $handler);

        $this->assertSame($expected, $result);
    }

    public function testPostWithValidTokenPassesThrough(): void
    {
        // Session contains the matching token
        $this->session->method('has')->with('__csrf')->willReturn(true);
        // Called both for validation (with default '') and for reuse (without default)
        $this->session->method('get')->willReturn('valid-token');
        $this->guard->expects($this->never())->method('validateToken');
        $this->guard->expects($this->never())->method('generateToken');

        $expected = new EmptyResponse();

        $result = $this->middleware->process(
            $this->makeRequest('POST', ['__csrf' => 'valid-token']),
            $this->handlerReturning($expected)
        );

        $this->assertSame($expected, $result);
    }

    public function testPostWithInvalidTokenRedirectsToSamePath(): void
    {
        // Session contains a different token
        $this->session->method('get')->with('__csrf', '')->willReturn('session-token');
        $this->guard->expects($this->never())->method('validateToken');
        $this->guard->expects($this->never())->method('generateToken');

        $request = (new ServerRequest(uri: 'https://example.com/lpa/123/type'))
            ->withMethod('POST')
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withParsedBody(['__csrf' => 'bad-token']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/lpa/123/type', $result->getHeaderLine('Location'));
    }

    public function testPostWithMissingCsrfFieldRedirects(): void
    {
        // Session has a token but POST body doesn't include __csrf
        $this->session->method('get')->with('__csrf', '')->willReturn('some-token');

        $result = $this->middleware->process(
            $this->makeRequest('POST', ['other' => 'data']),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testPostWithEmptyCsrfFieldRedirects(): void
    {
        $this->session->method('get')->with('__csrf', '')->willReturn('some-token');

        $result = $this->middleware->process(
            $this->makeRequest('POST', ['__csrf' => '']),
            $this->createMock(RequestHandlerInterface::class)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testTokenAttributeNameIsCorrect(): void
    {
        $this->assertEquals('csrfToken', CsrfValidationMiddleware::TOKEN_ATTRIBUTE);
    }

    public function testJsonPostBypassesCsrfValidationAndPassesThrough(): void
    {
        // No __csrf in session or body, but it's a JSON request — should pass through
        $this->session->method('has')->with('__csrf')->willReturn(false);
        $this->guard->method('generateToken')->willReturn('new-token');

        $expected = new EmptyResponse();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        $result = $this->middleware->process($request, $this->handlerReturning($expected));

        $this->assertSame($expected, $result);
    }

    public function testJsonPostWithCharsetBypassesCsrfValidation(): void
    {
        $this->session->method('has')->with('__csrf')->willReturn(false);
        $this->guard->method('generateToken')->willReturn('new-token');

        $expected = new EmptyResponse();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        $result = $this->middleware->process($request, $this->handlerReturning($expected));

        $this->assertSame($expected, $result);
    }

    public function testFormPostWithoutCsrfStillRedirects(): void
    {
        // Confirm that form/urlencoded POST without __csrf is still rejected
        $this->session->method('get')->with('__csrf', '')->willReturn('some-token');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withAttribute(CsrfMiddleware::GUARD_ATTRIBUTE, $this->guard)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withParsedBody(['other' => 'data']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}
