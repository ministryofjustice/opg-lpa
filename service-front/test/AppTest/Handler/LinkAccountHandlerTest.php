<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\LinkAccountForm;
use App\Handler\LinkAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\SignedOutSubjects;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\LinkReason;
use MakeShared\OneLogin\UserType;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LinkAccountHandlerTest extends TestCase
{
    private const string PENDING_SUB = 'urn:fdc:gov.uk:2022:newuser';
    private const string PENDING_ID_TOKEN = 'pending.id.token';

    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private LinkAccountForm $form;
    private LinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer           = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->oneLoginService    = $this->createMock(OneLoginService::class);
        $this->logger             = $this->createMock(LoggerInterface::class);
        $this->session            = $this->createMock(SessionInterface::class);

        $this->form = new LinkAccountForm();
        $this->form->init();

        $this->formElementManager->method('get')->with(LinkAccountForm::class)->willReturn($this->form);

        $this->handler = new LinkAccountHandler(
            $this->renderer,
            $this->formElementManager,
            $this->oneLoginService,
            new OneLoginSessionManager($this->createStub(SignedOutSubjects::class), new NullLogger()),
            $this->logger,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?string $pendingSub = self::PENDING_SUB,
        ?string $preAuthUrl = null,
        ?string $userType = 'lay',
        ?bool $keepExistingAccount = true,
    ): ServerRequest {
        $pendingLink = $pendingSub === null
            ? null
            : [
                'sub'        => $pendingSub,
                'email'      => 'newuser@example.com',
                'idToken'    => self::PENDING_ID_TOKEN,
                'signedInAt' => time(),
            ];

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'onelogin_pending_link' => $pendingLink,
                'onelogin_onboarding'   => [
                    'userType'            => $userType,
                    'hasExistingAccount'  => true,
                    'keepExistingAccount' => $keepExistingAccount,
                ],
                'pre_auth_request_url'  => $preAuthUrl,
                default                 => null,
            });

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
                'application/authenticated/onboarding/link-account.twig',
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
            ->with('auth.onelogin.onboarding_missing_pending_link');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testUserWhoHasNotSaidWhichBestDescribesThemIsAskedFirst(): void
    {
        $this->oneLoginService->expects($this->never())->method('linkExistingAccount');

        $response = $this->handler->handle($this->createRequest('GET', [], self::PENDING_SUB, null, null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/which-best-describes-you', $response->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{?bool}>
     */
    public static function notKeepingProvider(): array
    {
        return ['not answered' => [null], 'chose not to keep' => [false]];
    }

    #[DataProvider('notKeepingProvider')]
    public function testOnlyAUserKeepingTheirAccountIsAskedToSignInToIt(?bool $keepExistingAccount): void
    {
        $this->oneLoginService->expects($this->never())->method('linkExistingAccount');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], self::PENDING_SUB, null, 'lay', $keepExistingAccount)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/keep-your-lpas', $response->getHeaderLine('Location'));
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

    public function testSuccessfulLinkEstablishesSessionWithIdTokenAndRedirectsToDashboard(): void
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
            ->with($email, $word, self::PENDING_SUB, 'newuser@example.com', UserType::Lay)
            ->willReturn(['linked' => true, 'identity' => $identity]);

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->once())->method('clear');
        $written = [];
        $this->session->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$written): void {
                $written[$key] = $value;
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertSame(['identity' => $identity, 'onelogin_id_token' => self::PENDING_ID_TOKEN], $written);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard?account=linked', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLinkReturnsUserToTheirDeepLink(): void
    {
        $response = $this->handleSuccessfulLink('/lpa/12345678/checkout');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/12345678/checkout', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLinkIgnoresAnOffSitePreAuthUrl(): void
    {
        $response = $this->handleSuccessfulLink('//evil.example/');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard?account=linked', $response->getHeaderLine('Location'));
    }

    private function handleSuccessfulLink(?string $preAuthUrl): ResponseInterface
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $this->oneLoginService->method('linkExistingAccount')->willReturn([
            'linked'   => true,
            'identity' => [
                'userId'         => 'uid-1',
                'token'          => 'tok-abc',
                'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
                'lastLogin'      => '2025-01-01T00:00:00+00:00',
                'sharedSpaceId'  => null,
            ],
        ]);

        return $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word], self::PENDING_SUB, $preAuthUrl)
        );
    }

    public function testAlreadyLinkedRedirectsToCannotLinkPage(): void
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $this->oneLoginService->expects($this->once())
            ->method('linkExistingAccount')
            ->with($email, $word, self::PENDING_SUB, 'newuser@example.com')
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

    public function testPostWithAnEmailAddressInTheWrongFormatRendersTheFormWithoutTryingToLink(): void
    {
        $this->oneLoginService->expects($this->never())->method('linkExistingAccount');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => 'not-an-email', 'password' => 'guessable']) // pragma: allowlist secret
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertArrayHasKey('invalidEmailAddress', $this->form->get('email')->getMessages());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function failedLinkProvider(): array
    {
        return [
            'wrong password'  => [LinkReason::INVALID_CREDENTIALS],
            'unknown account' => [LinkReason::ACCOUNT_NOT_FOUND],
            'deleted account' => [LinkReason::ACCOUNT_DELETED],
            'locked account'  => [LinkReason::ACCOUNT_LOCKED],
            'inactive'        => [LinkReason::ACCOUNT_NOT_ACTIVE],
        ];
    }

    #[DataProvider('failedLinkProvider')]
    public function testAnyOtherFailureGoesToTheCannotLinkPage(string $reason): void
    {
        $this->oneLoginService->method('linkExistingAccount')->willReturn(['linked' => false, 'reason' => $reason]);
        $this->logger->expects($this->once())->method('info')->with('auth.onelogin.link_failed', ['reason' => $reason]);
        $this->session->expects($this->never())->method('set');
        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => 'my.email@example.com', 'password' => 'guessable']) // pragma: allowlist secret
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/cannot-link-account', $response->getHeaderLine('Location'));
    }

    public function testAnApiFailureGoesToTheCannotLinkPage(): void
    {
        $this->oneLoginService->method('linkExistingAccount')->willThrowException(new \RuntimeException('api down'));
        $this->logger->expects($this->once())->method('error')->with('auth.onelogin.link_error');
        $this->session->expects($this->never())->method('set');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => 'my.email@example.com', 'password' => 'guessable']) // pragma: allowlist secret
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/cannot-link-account', $response->getHeaderLine('Location'));
    }
}
