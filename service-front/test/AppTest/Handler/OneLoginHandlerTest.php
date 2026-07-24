<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OneLoginHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SessionInterface&MockObject $session;
    private OneLoginHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->handler = new OneLoginHandler(
            $this->renderer,
        );
    }

    private function createRequestWithSession(string $method = 'GET', ?array $parsedBody = null, ?string $state = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }

        if ($state !== null) {
            $request = $request->withAttribute('state', $state);
        }

        return $request;
    }

    public function testAuthenticatedUserIsRedirectedToDashboard(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);

        $response = $this->handler->handle($this->createRequestWithSession());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRendersOneloginTemplate(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/onelogin.twig')
            ->willReturn('<html>onelogin page</html>');

        $response = $this->handler->handle($this->createRequestWithSession());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
