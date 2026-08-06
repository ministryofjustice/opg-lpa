<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Form\User\Login;
use App\Handler\LinkAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\LinkReason;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LinkAccountHandlerTest extends TestCase
{
    private const string PENDING_SUB = 'urn:fdc:gov.uk:2022:newuser';

    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private Login $form;
    private LinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer           = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->oneLoginService    = $this->createMock(OneLoginService::class);
        $this->logger             = $this->createMock(LoggerInterface::class);
        $this->session            = $this->createMock(SessionInterface::class);

        $this->form = new Login();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new LinkAccountHandler(
            $this->renderer,
            $this->formElementManager,
            $this->oneLoginService,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?string $pendingSub = self::PENDING_SUB,
    ): ServerRequest {
        $pendingLink = $pendingSub === null
            ? null
            : ['sub' => $pendingSub, 'email' => 'newuser@example.com'];

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => $key === 'onelogin_pending_link' ? $pendingLink : null);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/link-account'))
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
                'application/authenticated/linking/link-account.twig',
                $this->callback(fn(array $vars) => isset($vars['form']) && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testMissingPendingSubRedirectsToLogin(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.link_missing_pending_sub');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->oneLoginService->expects($this->never())->method('linkExistingAccount');
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulLinkEstablishesSessionAndRedirectsToDashboard(): void
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $identity = [
            'userId'         => 'uid-1',
            'token'          => 'tok-abc',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->oneLoginService->expects($this->once())
            ->method('linkExistingAccount')
            ->with($email, $word, self::PENDING_SUB)
            ->willReturn(['linked' => true, 'identity' => $identity]);

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())
            ->method('set')
            ->with('identity', $identity);

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testAlreadyLinkedRedirectsToCannotLinkPage(): void
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $this->oneLoginService->expects($this->once())
            ->method('linkExistingAccount')
            ->with($email, $word, self::PENDING_SUB)
            ->willReturn(['linked' => false, 'reason' => LinkReason::ALREADY_LINKED]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.link_rejected', ['reason' => LinkReason::ALREADY_LINKED]);

        // A rejected link must not sign the user in.
        $this->session->expects($this->never())->method('set');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/cannot-link-account', $response->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function correctableFailureProvider(): array
    {
        return [
            'wrong password'  => [LinkReason::INVALID_CREDENTIALS, 'authentication-failed'],
            'unknown account' => [LinkReason::ACCOUNT_NOT_FOUND, 'authentication-failed'],
            'deleted account' => [LinkReason::ACCOUNT_DELETED, 'authentication-failed'],
            'locked account'  => [LinkReason::ACCOUNT_LOCKED, 'locked'],
            'inactive'        => [LinkReason::ACCOUNT_NOT_ACTIVE, 'not-activated'],
        ];
    }

    /**
     * @dataProvider correctableFailureProvider
     */
    public function testCorrectableFailureStaysOnFormWithInlineError(string $reason, string $expectedAuthError): void
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $this->oneLoginService->expects($this->once())
            ->method('linkExistingAccount')
            ->with($email, $word, self::PENDING_SUB)
            ->willReturn(['linked' => false, 'reason' => $reason]);

        // A correctable failure keeps the user on the form and never signs them in.
        $this->session->expects($this->never())->method('set');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/linking/link-account.twig',
                $this->callback(fn(array $vars) => ($vars['authError'] ?? null) === $expectedAuthError),
            )
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
