<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\OneLogin\OneLoginService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OneLoginServiceTest extends TestCase
{
    private ApiClient&MockObject $apiClient;
    private OneLoginService $service;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->service   = new OneLoginService($this->apiClient);
    }

    // ─── start() ──────────────────────────────────────────────────────────

    public function testBackChannelLogoutPostsTokenAnonymouslyAndReturnsAccepted(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/auth/onelogin/backchannel-logout',
                ['logoutToken' => 'a.logout.token'],
                [],
                true,
            )
            ->willReturn(['accepted' => true]);

        $this->assertTrue($this->service->backChannelLogout('a.logout.token'));
    }

    public function testBackChannelLogoutReturnsFalseWhenTokenRejected(): void
    {
        $this->apiClient->method('httpPost')
            ->willReturn(['accepted' => false, 'reason' => 'invalid_signature']);

        $this->assertFalse($this->service->backChannelLogout('forged'));
    }

    public function testStartForwardsCorrectPathQueryAndAnonymousFlag(): void
    {
        $redirectUri    = 'https://localhost:7002/auth/redirect';
        $expectedResult = ['state' => 'abc123', 'nonce' => 'def456', 'url' => 'https://auth.example.com/authorize?x=y'];

        $this->apiClient
            ->expects($this->once())
            ->method('httpGet')
            ->with(
                '/v2/auth/onelogin/start',
                ['redirect_url' => $redirectUri],
                anonymous: true,
            )
            ->willReturn($expectedResult);

        $result = $this->service->start($redirectUri);

        $this->assertSame($expectedResult['state'], $result['state']);
        $this->assertSame($expectedResult['nonce'], $result['nonce']);
        $this->assertSame($expectedResult['url'], $result['url']);
    }

    public function testStartThrowsWhenStateIsMissing(): void
    {
        $this->apiClient->method('httpGet')->willReturn(['nonce' => 'x', 'url' => 'https://x']);

        $this->expectException(RuntimeException::class);

        $this->service->start('https://localhost:7002/auth/redirect');
    }

    public function testStartThrowsWhenNonceIsMissing(): void
    {
        $this->apiClient->method('httpGet')->willReturn(['state' => 'x', 'url' => 'https://x']);

        $this->expectException(RuntimeException::class);

        $this->service->start('https://localhost:7002/auth/redirect');
    }

    public function testStartThrowsWhenUrlIsMissing(): void
    {
        $this->apiClient->method('httpGet')->willReturn(['state' => 'x', 'nonce' => 'y']);

        $this->expectException(RuntimeException::class);

        $this->service->start('https://localhost:7002/auth/redirect');
    }

    public function testStartThrowsWhenStateIsBlank(): void
    {
        $this->apiClient->method('httpGet')->willReturn(['state' => '', 'nonce' => 'y', 'url' => 'https://x']);

        $this->expectException(RuntimeException::class);

        $this->service->start('https://localhost:7002/auth/redirect');
    }

    public function testStartThrowsWhenResponseIsNull(): void
    {
        $this->apiClient->method('httpGet')->willReturn(null);

        $this->expectException(RuntimeException::class);

        $this->service->start('https://localhost:7002/auth/redirect');
    }

    // ─── callback() ───────────────────────────────────────────────────────

    public function testCallbackPostsCorrectPayloadWithAnonymousFlag(): void
    {
        $linkedResponse = [
            'linked'   => true,
            'sub'      => 'urn:fdc:gov.uk:2022:abc',
            'email'    => 'user@example.com',
            'idToken'  => 'header.payload.sig',
            'identity' => [
                'userId'         => 'uid-1',
                'token'          => 'tok-abc',
                'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
                'lastLogin'      => '2025-01-01T00:00:00+00:00',
                'sharedSpaceId'  => 'shared-space-9',
            ],
        ];

        $this->apiClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/auth/onelogin/callback',
                [
                    'code'         => 'auth-code-123',
                    'state'        => 'state-abc',
                    'nonce'        => 'nonce-xyz',
                    'redirect_uri' => 'https://service.example.com/auth/redirect',
                ],
                [],
                true,
            )
            ->willReturn($linkedResponse);

        $result = $this->service->callback(
            'auth-code-123',
            'state-abc',
            'nonce-xyz',
            'https://service.example.com/auth/redirect',
        );

        $this->assertTrue($result['linked']);
        $this->assertSame('urn:fdc:gov.uk:2022:abc', $result['sub']);
        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame('header.payload.sig', $result['idToken']);
        $this->assertSame($linkedResponse['identity'], $result['identity'] ?? null);
    }

    public function testCallbackReturnsUnlinkedShape(): void
    {
        $unlinkedResponse = [
            'linked'  => false,
            'sub'     => 'urn:fdc:gov.uk:2022:new',
            'email'   => 'new@example.com',
            'idToken' => 'header.payload.sig',
        ];

        $this->apiClient->method('httpPost')->willReturn($unlinkedResponse);

        $result = $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');

        $this->assertFalse($result['linked']);
        $this->assertSame('urn:fdc:gov.uk:2022:new', $result['sub']);
        $this->assertSame('new@example.com', $result['email']);
        $this->assertSame('header.payload.sig', $result['idToken']);
        $this->assertArrayNotHasKey('identity', $result);
    }

    public function testCallbackThrowsWhenLinkedIsMissing(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['sub' => 'x', 'email' => 'x@x.com', 'idToken' => 'h.p.s']);

        $this->expectException(RuntimeException::class);

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testCallbackThrowsWhenSubIsMissing(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['linked' => false, 'email' => 'x@x.com', 'idToken' => 'h.p.s']);

        $this->expectException(RuntimeException::class);

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testCallbackThrowsWhenEmailIsMissing(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['linked' => false, 'sub' => 'x', 'idToken' => 'h.p.s']);

        $this->expectException(RuntimeException::class);

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testCallbackThrowsWhenLinkedButIdentityMissing(): void
    {
        $this->apiClient->method('httpPost')->willReturn([
            'linked'  => true,
            'sub'     => 'urn:x',
            'email'   => 'x@x.com',
            'idToken' => 'h.p.s',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('identity fields missing');

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testCallbackThrowsWhenIdTokenIsMissing(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['linked' => false, 'sub' => 'x', 'email' => 'x@x.com']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('idToken');

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testCallbackThrowsWhenResponseIsNull(): void
    {
        $this->apiClient->method('httpPost')->willReturn(null);

        $this->expectException(RuntimeException::class);

        $this->service->callback('c', 's', 'n', 'https://x/auth/redirect');
    }

    public function testLinkExistingAccountPostsPayloadAndReturnsIdentityOnSuccess(): void
    {
        $identity = [
            'userId'         => 'uid-1',
            'token'          => 'tok-abc',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => 'shared-space-9',
        ];

        $this->apiClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/auth/onelogin/link',
                [
                    'username'      => 'user@example.com',
                    'password'      => 'sup3r-secret', //pragma: allowlist secret
                    'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
                    'oneLoginEmail' => 'joe.bloggs@gmail.com',
                ],
                [],
                true,
            )
            ->willReturn(['linked' => true, 'identity' => $identity]);

        $result = $this->service->linkExistingAccount('user@example.com', 'sup3r-secret', 'urn:fdc:gov.uk:2022:new', 'joe.bloggs@gmail.com');

        $this->assertTrue($result['linked']);
        $this->assertSame($identity, $result['identity'] ?? null);
    }

    public function testLinkExistingAccountReturnsReasonWhenNotLinked(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['linked' => false, 'reason' => 'already-linked']);

        $result = $this->service->linkExistingAccount('user@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com');

        $this->assertFalse($result['linked']);
        $this->assertSame('already-linked', $result['reason'] ?? null);
        $this->assertArrayNotHasKey('identity', $result);
    }

    public function testLinkExistingAccountPropagatesClientException(): void
    {
        $this->apiClient->method('httpPost')->willThrowException(new RuntimeException('api down'));

        $this->expectException(RuntimeException::class);

        $this->service->linkExistingAccount('user@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com');
    }

    public function testCreateAndLinkAccountPostsSubAndReturnsIdentity(): void
    {
        $identity = [
            'userId'         => 'uid-new',
            'token'          => 'tok-new',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->apiClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/auth/onelogin/create',
                [
                    'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
                    'oneLoginEmail' => 'brand.new.user@gmail.com',
                ],
                [],
                true,
            )
            ->willReturn($identity);

        $result = $this->service->createAndLinkAccount('urn:fdc:gov.uk:2022:new', 'brand.new.user@gmail.com');

        $this->assertSame($identity, $result);
    }

    public function testCreateAndLinkAccountPropagatesClientException(): void
    {
        $this->apiClient->method('httpPost')->willThrowException(new RuntimeException('api down'));

        $this->expectException(RuntimeException::class);

        $this->service->createAndLinkAccount('urn:x', 'someone@example.com');
    }

    // ─── logoutUrl() ──────────────────────────────────────────────────────

    public function testLogoutUrlPostsIdTokenAnonymouslyAndReturnsUrl(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/auth/onelogin/logout',
                ['idToken' => 'header.payload.sig', 'postLogoutRedirectUri' => 'https://example.com/done'],
                [],
                true,
            )
            ->willReturn(['url' => 'https://oidc.example.com/logout?id_token_hint=header.payload.sig']);

        $this->assertSame(
            'https://oidc.example.com/logout?id_token_hint=header.payload.sig',
            $this->service->logoutUrl('header.payload.sig', 'https://example.com/done'),
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidLogoutResponseProvider(): array
    {
        return [
            'null response'  => [null],
            'missing url'    => [[]],
            'empty url'      => [['url' => '']],
            'non-string url' => [['url' => ['https://oidc.example.com/logout']]],
        ];
    }

    /**
     * @param mixed $response
     */
    #[DataProvider('invalidLogoutResponseProvider')]
    public function testLogoutUrlThrowsForInvalidResponse($response): void
    {
        $this->apiClient->method('httpPost')->willReturn($response);

        $this->expectException(RuntimeException::class);

        $this->service->logoutUrl('header.payload.sig', 'https://example.com/done');
    }
}
