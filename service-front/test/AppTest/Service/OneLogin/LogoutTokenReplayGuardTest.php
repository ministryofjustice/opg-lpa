<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\LogoutTokenReplayGuard;
use App\Service\Redis\RedisClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LogoutTokenReplayGuardTest extends TestCase
{
    private const string JTI = '30642c87-6167-413f-8ace-f1643c59e398';

    private RedisClient&MockObject $redis;
    private LogoutTokenReplayGuard $guard;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(RedisClient::class);
        $this->redis->method('open')->willReturn(true);

        $this->guard = new LogoutTokenReplayGuard($this->redis);
    }

    private function key(): string
    {
        return 'onelogin_logout_jti:' . hash('sha256', self::JTI);
    }

    private function token(array $claims = ['jti' => self::JTI]): string
    {
        $encode = static fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');

        return $encode(['alg' => 'ES256']) . '.' . $encode($claims) . '.signature';
    }

    public function testFirstSightOfATokenClaimsItsJtiForThreeMinutes(): void
    {
        $this->redis->expects($this->once())
            ->method('writeIfAbsent')
            ->with($this->key(), '1', 180)
            ->willReturn(true);

        $this->assertTrue($this->guard->claim($this->token()));
    }

    public function testATokenWhoseJtiIsAlreadyClaimedIsAReplay(): void
    {
        $this->redis->method('writeIfAbsent')->willReturn(false);

        $this->assertFalse($this->guard->claim($this->token()));
    }

    /**
     * Without a readable jti there is nothing to claim; the API rejects the token anyway.
     */
    public static function tokenWithoutAJtiProvider(): array
    {
        return [
            'not a JWT'            => ['not-a-jwt'],
            'payload is not JSON'  => ['header.' . rtrim(base64_encode('{oops'), '=') . '.signature'],
            'no jti claim'         => [null],
            'jti is not a string'  => [['jti' => 42]],
            'jti is empty'         => [['jti' => '']],
        ];
    }

    #[DataProvider('tokenWithoutAJtiProvider')]
    public function testATokenWithoutAJtiIsLetThroughWithoutClaiming(string|array|null $token): void
    {
        $token = match (true) {
            is_string($token) => $token,
            is_array($token)  => $this->token($token),
            default           => $this->token(['sub' => 'urn:fdc:gov.uk:2022:abc']),
        };

        $this->redis->expects($this->never())->method('writeIfAbsent');

        $this->assertTrue($this->guard->claim($token));
    }

    public function testClaimThrowsWhenRedisIsUnreachable(): void
    {
        $redis = $this->createMock(RedisClient::class);
        $redis->method('open')->willReturn(false);

        $this->expectException(RuntimeException::class);

        (new LogoutTokenReplayGuard($redis))->claim($this->token());
    }

    public function testReleaseFreesTheJtiSoARetryCanBeProcessed(): void
    {
        $this->redis->expects($this->once())->method('destroy')->with($this->key());

        $this->guard->release($this->token());
    }

    public function testReleaseDoesNothingForATokenWithoutAJti(): void
    {
        $this->redis->expects($this->never())->method('destroy');

        $this->guard->release('not-a-jwt');
    }

    public function testConnectsToRedisOnlyOnce(): void
    {
        $redis = $this->createMock(RedisClient::class);
        $redis->expects($this->once())->method('open')->willReturn(true);
        $redis->method('writeIfAbsent')->willReturn(true);

        $guard = new LogoutTokenReplayGuard($redis);
        $guard->claim($this->token());
        $guard->release($this->token());
    }
}
