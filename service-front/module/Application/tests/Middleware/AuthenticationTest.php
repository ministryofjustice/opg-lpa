<?php

namespace ApplicationTest\Middleware;

use Application\Middleware\Authentication;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use DateTime;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use Laminas\Session\SessionManager;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var RequestHandlerInterface&MockObject */
    private $handler;

    /** @var AuthenticationService&MockObject */
    private $authService;

    /** @var UserService&MockObject */
    private $userService;

    /** @var SessionManagerSupport&MockObject */
    private $sessionManagerSupport;

    /** @var UrlHelper&MockObject */
    private $urlHelper;

    private Authentication $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->authService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $config = [
            'terms' => [
                'lastUpdated' => '2025-01-01T00:00:00',
            ],
        ];

        $this->sut = new Authentication(
            $this->authService,
            $this->userService,
            $this->sessionManagerSupport,
            $this->urlHelper,
            $config
        );

        $this->request
            ->method('withAttribute')
            ->willReturnSelf();

        $this->request
            ->method('getUri')
            ->willReturn(new Uri('https://example.com/user/about-you'));
    }

    public function testRedirectsToLoginWithTimeoutWhenNotAuthenticatedAndNoFailureCode(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->method('get')
            ->willReturnMap([
                ['PreAuthRequest', [], []],
                ['AuthFailureReason', [], []],
            ]);

        $session
            ->expects($this->once())
            ->method('set')
            ->with(
                'PreAuthRequest',
                $this->callback(function (array $value) {
                    return isset($value['url'])
                        && $value['url'] === 'https://example.com/user/about-you';
                })
            );

        $this->request
            ->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $this->authService
            ->method('getIdentity')
            ->willReturn(null);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('login', [], ['state' => 'timeout'])
            ->willReturn('/login?state=timeout');

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login?state=timeout', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToLoginWithInternalSystemErrorWhenAuthFailureCodePresent(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->method('get')
            ->willReturnMap([
                ['PreAuthRequest', [], []],
                ['AuthFailureReason', [], ['code' => 'ERR']],
            ]);

        $session
            ->expects($this->once())
            ->method('set')
            ->with(
                'PreAuthRequest',
                $this->callback(function (array $value) {
                    return isset($value['url'])
                        && $value['url'] === 'https://example.com/user/about-you';
                })
            );

        $this->request
            ->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $this->authService
            ->method('getIdentity')
            ->willReturn(null);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('login', [], ['state' => 'internalSystemError'])
            ->willReturn('/login?state=internalSystemError');

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login?state=internalSystemError', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToTermsChangedWhenTermsNotSeenAndLastLoginBeforeLastUpdated(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->method('get')
            ->willReturnMap([
                ['PreAuthRequest', [], []],
                ['AuthFailureReason', [], []],
                ['TermsAndConditionsCheck', [], []],
            ]);

        $session
            ->expects($this->once())
            ->method('set')
            ->with(
                'TermsAndConditionsCheck',
                $this->callback(function (array $value) {
                    return !empty($value['seen']);
                })
            );

        $this->request
            ->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $identity = $this->createMock(Identity::class);
        $identity
            ->method('lastLogin')
            ->willReturn(new DateTime('2023-12-31T00:00:00'));

        $this->authService
            ->method('getIdentity')
            ->willReturn($identity);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('user/dashboard/terms-changed')
            ->willReturn('/user/dashboard/terms-changed');

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard/terms-changed', $response->getHeaderLine('Location'));
    }

    public function testInvalidUserClearsIdentityDestroysSessionAndRedirectsToLoginTimeout(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $identity = $this->createMock(Identity::class);
        $identity
            ->method('lastLogin')
            ->willReturn(new DateTime('2024-01-02T00:00:00'));
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('+1 hour'));

        $this->authService
            ->method('getIdentity')
            ->willReturn($identity);

        $badUser = $this->createMock(User::class);
        $badUser
            ->expects($this->once())
            ->method('toArray')
            ->willThrowException(new \RuntimeException('broken'));

        $session
            ->method('get')
            ->willReturnMap([
                ['PreAuthRequest', [], []],
                ['AuthFailureReason', [], []],
                ['TermsAndConditionsCheck', [], ['seen' => true]],
                ['userDetails', [], ['user' => $badUser]],
            ]);

        $this->request
            ->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('getSessionManager')
            ->willReturn($sessionManager);

        $this->authService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('login', [], ['state' => 'timeout'])
            ->willReturn('/login?state=timeout');

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->sut->process($this->request, $this->handler);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login?state=timeout', $response->getHeaderLine('Location'));
    }

    public function testHappyPathCallsHandlerAndSetsAttributes(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $identity = $this->createMock(Identity::class);
        $identity
            ->method('lastLogin')
            ->willReturn(new DateTime('2024-01-02T00:00:00'));
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('+1 hour'));

        $this->authService
            ->method('getIdentity')
            ->willReturn($identity);

        $user = $this->createMock(User::class);
        $user
            ->method('toArray')
            ->willReturn(['id' => '123']);

        $session
            ->method('get')
            ->willReturnMap([
                ['PreAuthRequest', [], []],
                ['AuthFailureReason', [], []],
                ['TermsAndConditionsCheck', [], ['seen' => true]],
                ['userDetails', [], ['user' => $user]],
            ]);

        $this->request
            ->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $request) use ($identity, $user) {
                $this->assertSame($identity, $request->getAttribute(Identity::class));
                $this->assertSame($user, $request->getAttribute(User::class));

                $seconds = $request->getAttribute('secondsUntilSessionExpires');
                $this->assertIsInt($seconds);
                $this->assertGreaterThan(0, $seconds);

                return true;
            }))
            ->willReturn(new Response());

        $this->urlHelper
            ->expects($this->never())
            ->method('generate');

        $response = $this->sut->process($this->request, $this->handler);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
