<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Form\User\LinkOrCreateAccountForm;
use App\Handler\LinkOrCreateAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LinkOrCreateAccountHandlerTest extends TestCase
{
    private const array PENDING_LINK = [
        'sub'   => 'urn:fdc:gov.uk:2022:newuser',
        'email' => 'newuser@example.com',
    ];

    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private LinkOrCreateAccountForm $form;
    private LinkOrCreateAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->form = new LinkOrCreateAccountForm();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new LinkOrCreateAccountHandler(
            $this->renderer,
            $this->formElementManager,
            new OneLoginSessionManager(),
            $this->oneLoginService,
            $this->logger,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?array $pendingLink = self::PENDING_LINK,
        ?string $preAuthUrl = null,
    ): ServerRequest {
        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'onelogin_pending_link' => $pendingLink,
                'pre_auth_request_url'  => $preAuthUrl,
                default                 => null,
            });

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/link-or-create-account'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/linking/link-or-create-account.twig',
                $this->callback(fn(array $vars) => isset($vars['form']) && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testMissingPendingLinkRedirectsToLogin(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.link_or_create_missing_pending_link');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testLinkChoiceRedirectsToLinkAccount(): void
    {
        $this->oneLoginService->expects($this->never())->method('createAndLinkAccount');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['choice' => 'link'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/link-account', $response->getHeaderLine('Location'));
    }

    public function testCreateChoiceCreatesAccountEstablishesSessionAndRedirectsToDashboard(): void
    {
        $identity = [
            'userId'         => 'uid-new',
            'token'          => 'tok-new',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->oneLoginService->expects($this->once())
            ->method('createAndLinkAccount')
            ->with(self::PENDING_LINK['sub'], self::PENDING_LINK['email'])
            ->willReturn($identity);

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())
            ->method('set')
            ->with('identity', $identity);

        $response = $this->handler->handle(
            $this->createRequest('POST', ['choice' => 'create'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testCreateChoiceReturnsUserToTheirDeepLink(): void
    {
        $response = $this->handleCreateChoice('/lpa/12345678/checkout');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/12345678/checkout', $response->getHeaderLine('Location'));
    }

    public function testCreateChoiceIgnoresAnOffSitePreAuthUrl(): void
    {
        $response = $this->handleCreateChoice('//evil.example/');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    private function handleCreateChoice(?string $preAuthUrl): ResponseInterface
    {
        $this->oneLoginService->method('createAndLinkAccount')->willReturn([
            'userId'         => 'uid-new',
            'token'          => 'tok-new',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ]);

        return $this->handler->handle(
            $this->createRequest('POST', ['choice' => 'create'], self::PENDING_LINK, $preAuthUrl)
        );
    }

    public function testCreateChoiceApiFailureKeepsUserOnFormWithError(): void
    {
        $this->oneLoginService->expects($this->once())
            ->method('createAndLinkAccount')
            ->with(self::PENDING_LINK['sub'], self::PENDING_LINK['email'])
            ->willThrowException(new \RuntimeException('api down'));

        $this->session->expects($this->never())->method('set');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('auth.onelogin.create_error', $this->anything());

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/linking/link-or-create-account.twig',
                $this->callback(fn(array $vars) => ($vars['error'] ?? null) === 'api-error'),
            )
            ->willReturn('<html>form with error</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['choice' => 'create'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
