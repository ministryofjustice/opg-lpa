<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\OneLogin\OneLoginService;
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

    public function testStartForwardsCorrectPathQueryAndAnonymousFlag(): void
    {
        $redirectUri   = 'https://localhost:7002/auth/redirect';
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
}
