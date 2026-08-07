<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\CannotLinkAccountHandler;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CannotLinkAccountHandlerTest extends TestCase
{
    private const string PENDING_SUB = 'urn:fdc:gov.uk:2022:newuser';

    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private CannotLinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->session  = $this->createMock(SessionInterface::class);

        $this->handler = new CannotLinkAccountHandler(
            $this->renderer,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    private function createRequest(?string $pendingSub = self::PENDING_SUB): ServerRequest
    {
        $pendingLink = $pendingSub === null
            ? null
            : ['sub' => $pendingSub, 'email' => 'newuser@example.com'];

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => $key === 'onelogin_pending_link' ? $pendingLink : null);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new Uri('/cannot-link-account'))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testRendersPageWhenPendingLinkPresent(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/linking/cannot-link-account.twig')
            ->willReturn('<html>cannot link</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRedirectsToLoginWhenNoPendingLink(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.cannot_link_missing_pending_link');

        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest(null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }
}
