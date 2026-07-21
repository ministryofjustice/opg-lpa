<?php

declare(strict_types=1);

namespace AppTest\Service\Alb;

use App\Service\Alb\MockAlbTokenClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class MockAlbTokenClientTest extends TestCase
{
    private ClientInterface|MockObject $httpClient;
    private MockAlbTokenClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->client = new MockAlbTokenClient($this->httpClient, 'https://mock-cognito.example');
    }

    public function testFetchTestTokenReturnsToken(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"token":"test-jwt"}'));

        self::assertSame('test-jwt', $this->client->fetchTestToken('dev@example.com'));
    }

    public function testFetchTestTokenThrowsOnHttpError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch test token from mock ALB/Cognito server');

        $this->client->fetchTestToken('dev@example.com');
    }
}
