<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\SignedOutSubjects;
use App\Service\Redis\RedisClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SignedOutSubjectsTest extends TestCase
{
    private const string SUB = 'urn:fdc:gov.uk:2022:abc';

    private const int SIGNED_IN_AT = 1791280000;

    private RedisClient&MockObject $redis;
    private SignedOutSubjects $signedOutSubjects;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(RedisClient::class);
        $this->redis->method('open')->willReturn(true);

        $this->signedOutSubjects = new SignedOutSubjects($this->redis);
    }

    private function key(): string
    {
        return 'onelogin_signed_out:' . hash('sha256', self::SUB);
    }

    public function testRecordSignOutStoresTheTimeKeyedOnAHashOfTheSub(): void
    {
        $this->redis->method('read')->willReturn('');
        $this->redis->expects($this->once())
            ->method('write')
            ->with($this->key(), '1791283600')
            ->willReturn(true);

        $this->signedOutSubjects->recordSignOut(self::SUB, 1791283600);
    }

    public function testRecordSignOutKeepsTheLatestWhenNotificationsArriveOutOfOrder(): void
    {
        $this->redis->method('read')->willReturn('1791283600');
        $this->redis->expects($this->once())
            ->method('write')
            ->with($this->key(), '1791283600')
            ->willReturn(true);

        $this->signedOutSubjects->recordSignOut(self::SUB, 1791281000);
    }

    public function testRecordSignOutThrowsWhenItCannotBeStored(): void
    {
        $this->redis->method('read')->willReturn('');
        $this->redis->method('write')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $this->signedOutSubjects->recordSignOut(self::SUB, 1791283600);
    }

    public function testSignedOutAfterTheSignInBlocksThePendingLink(): void
    {
        $this->redis->method('read')->with($this->key())->willReturn((string) (self::SIGNED_IN_AT + 600));

        $this->assertTrue($this->signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT));
    }

    public function testSignedOutAtTheSameSecondAsTheSignInBlocksThePendingLink(): void
    {
        $this->redis->method('read')->willReturn((string) self::SIGNED_IN_AT);

        $this->assertTrue($this->signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT));
    }

    public function testSignOutBeforeANewerSignInDoesNotBlockIt(): void
    {
        $this->redis->method('read')->willReturn((string) (self::SIGNED_IN_AT - 600));

        $this->assertFalse($this->signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT));
    }

    public function testNoRecordedSignOutDoesNotBlock(): void
    {
        $this->redis->method('read')->willReturn('');

        $this->assertFalse($this->signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT));
    }

    public function testConnectsOnceAndReusesTheConnection(): void
    {
        $redis = $this->createMock(RedisClient::class);
        $redis->expects($this->once())->method('open')->willReturn(true);
        $redis->method('read')->willReturn('');

        $signedOutSubjects = new SignedOutSubjects($redis);
        $signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT);
        $signedOutSubjects->signedOutSince(self::SUB, self::SIGNED_IN_AT);
    }

    public function testThrowsWhenRedisIsUnreachable(): void
    {
        $redis = $this->createMock(RedisClient::class);
        $redis->method('open')->willReturn(false);

        $this->expectException(RuntimeException::class);

        (new SignedOutSubjects($redis))->signedOutSince(self::SUB, self::SIGNED_IN_AT);
    }
}
