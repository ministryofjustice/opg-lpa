<?php

declare(strict_types=1);

namespace AppTest\Middleware\Session;

use App\Middleware\Session\CsrfMiddleware;
use App\RequestAttributes;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private function makeHandler(int $status = 200): RequestHandlerInterface
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::type(ServerRequestInterface::class))
            ->willReturn((new HttpFactory())->createResponse($status));
        return $handler->reveal();
    }

    private function makeSession(?string $csrfToken = null): SessionInterface
    {
        $session = $this->prophesize(SessionInterface::class);

        if ($csrfToken !== null) {
            $session->has('csrf')->willReturn(true);
            $session->get('csrf')->willReturn($csrfToken);
        } else {
            $token = str_repeat('a', 64);
            $session->has('csrf')->willReturn(false);
            $session->set('csrf', \Prophecy\Argument::type('string'))->shouldBeCalled();
            $session->get('csrf')->willReturn($token);
        }

        return $session->reveal();
    }

    public function testSetsCsrfTokenOnRequest(): void
    {
        $middleware = new CsrfMiddleware();
        $token = str_repeat('x', 64);

        $capturedRequest = null;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::that(function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return true;
        }))->willReturn((new HttpFactory())->createResponse(200));

        $request = (new ServerRequest())
            ->withAttribute(SessionInterface::class, $this->makeSession($token));

        $middleware->process($request, $handler->reveal());

        $this->assertSame($token, $capturedRequest->getAttribute(RequestAttributes::CSRF_TOKEN));
    }

    public function testGeneratesTokenWhenNotInSession(): void
    {
        $middleware = new CsrfMiddleware();

        $capturedRequest = null;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::that(function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return true;
        }))->willReturn((new HttpFactory())->createResponse(200));

        $request = (new ServerRequest())
            ->withAttribute(SessionInterface::class, $this->makeSession());

        $middleware->process($request, $handler->reveal());

        $this->assertNotNull($capturedRequest->getAttribute(RequestAttributes::CSRF_TOKEN));
    }
}
