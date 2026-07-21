<?php

declare(strict_types=1);

namespace AppTest\Service\Alb;

use App\Service\Alb\PublicKeyClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class PublicKeyClientTest extends TestCase
{
    private ClientInterface|MockObject $httpClient;
    private PublicKeyClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        // cacheTtl: 0 disables APCu caching so each test hits the mocked HTTP client.
        $this->client = new PublicKeyClient($this->httpClient, 'https://public-keys.example', 0);
    }

    public function testFetchPublicKeyReturnsPemBody(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                self::assertSame('https://public-keys.example/test-kid', (string) $request->getUri());
                return true;
            }))
            ->willReturn(new Response(200, [], "-----BEGIN PUBLIC KEY-----\nabc\n-----END PUBLIC KEY-----"));

        self::assertSame(
            "-----BEGIN PUBLIC KEY-----\nabc\n-----END PUBLIC KEY-----",
            $this->client->fetchPublicKey('test-kid'),
        );
    }

    public function testFetchPublicKeyTrimsTrailingSlashFromBaseUrl(): void
    {
        $client = new PublicKeyClient($this->httpClient, 'https://public-keys.example/', 0);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                self::assertSame('https://public-keys.example/test-kid', (string) $request->getUri());
                return true;
            }))
            ->willReturn(new Response(200, [], 'pem-key'));

        self::assertSame('pem-key', $client->fetchPublicKey('test-kid'));
    }

    public function testFetchPublicKeyThrowsOnHttpError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch ALB public key');

        $this->client->fetchPublicKey('test-kid');
    }

    public function testFetchPublicKeyThrowsOnNon200Status(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(404, [], 'not found'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected status 404 fetching ALB public key for kid "test-kid"');

        $this->client->fetchPublicKey('test-kid');
    }
}
