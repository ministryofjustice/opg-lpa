<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\OnboardingState;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\PendingLink;
use App\Service\OneLogin\SignedOutSubjects;
use MakeShared\OneLogin\UserType;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OneLoginSessionManagerTest extends TestCase
{
    private const string SESSION_KEY = 'onelogin_pending_link';
    private const string ID_TOKEN_KEY = 'onelogin_id_token';
    private const string ID_TOKEN = 'header.payload.sig';

    private SessionInterface&MockObject $session;
    private SignedOutSubjects&MockObject $signedOutSubjects;
    private LoggerInterface&MockObject $logger;
    private OneLoginSessionManager $manager;

    protected function setUp(): void
    {
        $this->session           = $this->createMock(SessionInterface::class);
        $this->signedOutSubjects = $this->createMock(SignedOutSubjects::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->manager           = new OneLoginSessionManager($this->signedOutSubjects, $this->logger);
    }

    public function testSetPendingLinkStoresSubEmailIdTokenAndSignInTimeUnderTheOwnedKey(): void
    {
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(self::SESSION_KEY, [
                'sub'        => 'urn:fdc:gov.uk:2022:newuser',
                'email'      => 'newuser@example.com',
                'idToken'    => self::ID_TOKEN,
                'signedInAt' => 1791280000,
            ]);

        $this->manager->setPendingLink(
            $this->session,
            'urn:fdc:gov.uk:2022:newuser',
            'newuser@example.com',
            self::ID_TOKEN,
            1791280000,
        );
    }

    public function testGetPendingLinkReturnsDtoFromStoredData(): void
    {
        $this->session
            ->method('get')
            ->with(self::SESSION_KEY)
            ->willReturn([
                'sub'        => 'urn:fdc:gov.uk:2022:newuser',
                'email'      => 'newuser@example.com',
                'idToken'    => self::ID_TOKEN,
                'signedInAt' => $signedInAt = time() - 60,
            ]);

        $pendingLink = $this->manager->getPendingLink($this->session);

        $this->assertInstanceOf(PendingLink::class, $pendingLink);
        $this->assertSame('urn:fdc:gov.uk:2022:newuser', $pendingLink->sub);
        $this->assertSame('newuser@example.com', $pendingLink->email);
        $this->assertSame(self::ID_TOKEN, $pendingLink->idToken);
        $this->assertSame($signedInAt, $pendingLink->signedInAt);
    }

    public function testPendingLinkIsStillUsableJustInsideTheMaximumAge(): void
    {
        $this->session->method('get')->willReturn($this->storedPendingLink(
            time() - OneLoginSessionManager::PENDING_LINK_MAX_AGE_SECONDS + 60,
        ));

        $this->assertInstanceOf(PendingLink::class, $this->manager->getPendingLink($this->session));
    }

    public function testPendingLinkOlderThanTheMaximumAgeIsNotUsable(): void
    {
        $this->session->method('get')->willReturn($this->storedPendingLink(
            time() - OneLoginSessionManager::PENDING_LINK_MAX_AGE_SECONDS - 1,
        ));

        $this->assertNull($this->manager->getPendingLink($this->session));
    }

    public function testPendingLinkIsClearedOnceTheUserHasSignedOutOfOneLogin(): void
    {
        $signedInAt = time() - 60;
        $this->session->method('get')->willReturn($this->storedPendingLink($signedInAt));

        $this->signedOutSubjects->expects($this->once())
            ->method('signedOutSince')
            ->with('urn:fdc:gov.uk:2022:newuser', $signedInAt)
            ->willReturn(true);
        $this->session->expects($this->once())->method('unset')->with(self::SESSION_KEY);
        $this->logger->expects($this->once())->method('info')->with('auth.onelogin.pending_link_signed_out');

        $this->assertNull($this->manager->getPendingLink($this->session));
    }

    public function testPendingLinkIsUsableIfTheUserHasNotSignedOutSinceSigningIn(): void
    {
        $this->session->method('get')->willReturn($this->storedPendingLink(time() - 60));
        $this->signedOutSubjects->method('signedOutSince')->willReturn(false);
        $this->session->expects($this->never())->method('unset');

        $this->assertInstanceOf(PendingLink::class, $this->manager->getPendingLink($this->session));
    }

    /**
     * @return array{sub: string, email: string, idToken: string, signedInAt: int}
     */
    private function storedPendingLink(int $signedInAt): array
    {
        return [
            'sub'        => 'urn:fdc:gov.uk:2022:newuser',
            'email'      => 'newuser@example.com',
            'idToken'    => self::ID_TOKEN,
            'signedInAt' => $signedInAt,
        ];
    }

    public function testGetPendingLinkDefaultsEmailToEmptyStringWhenMissing(): void
    {
        $this->session
            ->method('get')
            ->willReturn(['sub' => 'urn:fdc:gov.uk:2022:newuser', 'idToken' => self::ID_TOKEN, 'signedInAt' => time()]);

        $pendingLink = $this->manager->getPendingLink($this->session);

        $this->assertInstanceOf(PendingLink::class, $pendingLink);
        $this->assertSame('', $pendingLink->email);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidPendingLinkProvider(): array
    {
        return [
            'nothing stored'    => [null],
            'not an array'      => ['a string'],
            'missing sub'       => [['email' => 'newuser@example.com']],
            'empty sub'         => [['sub' => '', 'email' => 'newuser@example.com']],
            'non-string sub'    => [['sub' => 123, 'email' => 'newuser@example.com']],
            'missing idToken'   => [['sub' => 'urn:fdc:gov.uk:2022:newuser', 'email' => 'newuser@example.com']],
            'empty idToken'     => [['sub' => 'urn:fdc:gov.uk:2022:newuser', 'idToken' => '']],
            'no sign-in time'   => [['sub' => 'urn:fdc:gov.uk:2022:newuser', 'idToken' => 'h.p.s']],
            'text sign-in time' => [['sub' => 'urn:fdc:gov.uk:2022:newuser', 'idToken' => 'h.p.s', 'signedInAt' => 'yesterday']],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('invalidPendingLinkProvider')]
    public function testGetPendingLinkReturnsNullForInvalidData($stored): void
    {
        $this->session->method('get')->willReturn($stored);

        $this->assertNull($this->manager->getPendingLink($this->session));
    }

    public function testClearPendingLinkUnsetsTheOwnedKey(): void
    {
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with(self::SESSION_KEY);

        $this->manager->clearPendingLink($this->session);
    }

    public function testStartSessionRegeneratesClearsAndStoresIdentityWithIdToken(): void
    {
        $identity = [
            'userId'         => 'uid-1',
            'token'          => 'tok-abc',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $calls = [];
        $this->session->method('regenerate')->willReturnCallback(function () use (&$calls): SessionInterface {
            $calls[] = 'regenerate';

            return $this->session;
        });
        $this->session->method('clear')->willReturnCallback(function () use (&$calls): void {
            $calls[] = 'clear';
        });
        $this->session->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$calls): void {
            $calls[] = [$key => $value];
        });

        $this->manager->startSession($this->session, $identity, self::ID_TOKEN);

        $this->assertSame(
            ['regenerate', 'clear', ['identity' => $identity], [self::ID_TOKEN_KEY => self::ID_TOKEN]],
            $calls,
        );
    }

    /**
     * @return array<string, array{?string, string, string}>
     */
    public static function finishOnboardingProvider(): array
    {
        return [
            'no page asked for first' => [null, 'linked', '/user/dashboard?account=linked'],
            'a page asked for first'  => ['/lpa/123/donor', 'created', '/lpa/123/donor'],
            'an off-site page'        => ['https://evil.example.com', 'created', '/user/dashboard?account=created'],
        ];
    }

    #[DataProvider('finishOnboardingProvider')]
    public function testFinishOnboardingSignsTheUserInAndSaysWhereToGo(?string $preAuthUrl, string $linkedOrCreated, string $expected): void
    {
        $this->session->method('get')->willReturnCallback(
            fn (string $key) => $key === 'pre_auth_request_url' ? $preAuthUrl : null,
        );
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $identity = [
            'userId'         => 'uid-1',
            'token'          => 'tok-abc',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->assertSame($expected, $this->manager->finishOnboarding($this->session, $identity, self::ID_TOKEN, $linkedOrCreated));
    }

    public function testForgetIdTokenRemovesIt(): void
    {
        $this->session->expects($this->once())->method('unset')->with(self::ID_TOKEN_KEY);

        $this->manager->forgetIdToken($this->session);
    }

    public function testGetIdTokenReturnsStoredToken(): void
    {
        $this->session->method('get')->with(self::ID_TOKEN_KEY)->willReturn(self::ID_TOKEN);

        $this->assertSame(self::ID_TOKEN, $this->manager->getIdToken($this->session));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidIdTokenProvider(): array
    {
        return [
            'nothing stored' => [null],
            'empty string'   => [''],
            'not a string'   => [['header.payload.sig']],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('invalidIdTokenProvider')]
    public function testGetIdTokenReturnsNullForInvalidData($stored): void
    {
        $this->session->method('get')->willReturn($stored);

        $this->assertNull($this->manager->getIdToken($this->session));
    }

    public function testGetOnboardingIsEmptyWhenNothingIsStored(): void
    {
        $this->session->method('get')->with('onelogin_onboarding')->willReturn(null);

        $this->assertNull($this->manager->getOnboarding($this->session)->userType);
    }

    public function testOnboardingAnswersAreSavedAndReadBack(): void
    {
        $stored = null;
        $this->session->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$stored): void {
            $this->assertSame('onelogin_onboarding', $key);
            $stored = $value;
        });
        $this->session->method('get')->willReturnCallback(
            function (string $key) use (&$stored): mixed {
                return $key === 'onelogin_onboarding' ? $stored : null;
            },
        );

        $this->manager->saveOnboarding($this->session, new OnboardingState(UserType::Professional));

        $this->assertSame(UserType::Professional, $this->manager->getOnboarding($this->session)->userType);
    }
}
