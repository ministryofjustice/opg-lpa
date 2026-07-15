<?php

declare(strict_types=1);

namespace AppTest\Service\Cognito;

use App\Service\Cognito\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class ClientTest extends TestCase
{
    private ClientInterface|MockObject $httpClient;
    private Client $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->client = new Client($this->httpClient, 'https://cognito.example', 0);
    }

    public function testFetchJwksReturnsDecodedJson(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"keys":[]}'));

        self::assertSame(['keys' => []], $this->client->fetchJwks());
    }

    public function testFetchJwksThrowsOnHttpError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch JWKS from Cognito');

        $this->client->fetchJwks();
    }

    public function testFetchTestTokenReturnsToken(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"id_token":"test-jwt"}'));

        self::assertSame('test-jwt', $this->client->fetchTestToken('dev@example.com'));
    }

    public function testFetchTestTokenThrowsOnHttpError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch test token from mock Cognito');

        $this->client->fetchTestToken('dev@example.com');
    }
}
