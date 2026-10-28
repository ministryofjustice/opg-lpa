<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\CannotLinkAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\SignedOutSubjects;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

use function array_key_exists;

class CannotLinkAccountHandlerTest extends TestCase
{
    private const string PENDING_SUB = 'urn:fdc:gov.uk:2022:newuser';

    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private UrlHelper&MockObject $urlHelper;
    private CannotLinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer  = $this->createMock(TemplateRendererInterface::class);
        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->session   = $this->createMock(SessionInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->handler = new CannotLinkAccountHandler(
            $this->renderer,
            new OneLoginSessionManager($this->createStub(SignedOutSubjects::class), new NullLogger()),
            $this->logger,
            $this->urlHelper,
        );
    }

    private function createRequest(?string $pendingSub = self::PENDING_SUB): ServerRequest
    {
        $pendingLink = $pendingSub === null
            ? null
            : ['sub' => $pendingSub, 'email' => 'newuser@example.com', 'idToken' => 'pending.id.token', 'signedInAt' => time()];

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
            ->with(
                'application/authenticated/linking/cannot-link-account.twig',
                $this->callback(fn(array $vars) => $vars['csrfToken'] === 'csrf-token'
                    && $vars['currentRouteName'] === 'cannot-link-account'
                    && array_key_exists('signedInUser', $vars))
            )
            ->willReturn('<html>cannot link</html>');

        $request = $this->createRequest()
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'csrf-token')
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'cannot-link-account');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<html>cannot link</html>', (string) $response->getBody());
    }

    public function testThrowsWhenSessionMissingOnGet(): void
    {
        $request = (new ServerRequest())->withMethod('GET');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session middleware is not configured');

        $this->handler->handle($request);
    }

    public function testPostClearsExistingAccountAndRedirectsToYourDetails(): void
    {
        $this->session->method('get')->willReturnCallback(
            fn(string $key) => $key === 'onelogin_onboarding' ? ['hasExistingAccount' => true] : null
        );
        $this->session->expects($this->once())
            ->method('set')
            ->with(
                'onelogin_onboarding',
                $this->callback(fn(array $data) => $data['hasExistingAccount'] === false)
            );
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('your-details')
            ->willReturn('/your-details');
        $this->renderer->expects($this->never())->method('render');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withUri(new Uri('/cannot-link-account'))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/your-details', $response->getHeaderLine('Location'));
    }

    public function testThrowsWhenSessionMissingOnPost(): void
    {
        $request = (new ServerRequest())->withMethod('POST');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session middleware is not configured');

        $this->handler->handle($request);
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
