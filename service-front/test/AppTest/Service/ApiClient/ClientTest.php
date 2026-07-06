<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Service\ApiClient\Client;
use App\Service\ApiClient\Exception\ApiException;
use Http\Client\HttpClient as HttpClientInterface;
use MakeShared\Telemetry\Tracer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class ClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructStoresDependenciesAndDefaults(): void
    {
        $client = $this->createClient(['Token' => 'initial-token']);

        $baseUri = new \ReflectionProperty($client, 'apiBaseUri');
        $headers = new \ReflectionProperty($client, 'defaultHeaders');

        $this->assertSame('https://api.example', $baseUri->getValue($client));
        $this->assertSame(['Token' => 'initial-token'], $headers->getValue($client));
    }

    public function testUpdateTokenUpdatesDefaultHeaders(): void
    {
        $client = $this->createClient(['Token' => 'old-token']);

        $client->updateToken('new-token');

        $headers = new \ReflectionProperty($client, 'defaultHeaders');

        $this->assertSame('new-token', $headers->getValue($client)['Token']);
    }

    public function testHttpGetSendsRequestAndReturnsDecodedJson(): void
    {
        $client = $this->createClient(['Token' => 'secret-token']);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && (string) $request->getUri() === 'https://api.example/users?active=1'
                    && $request->getHeaderLine('Token') === 'secret-token'
                    && $request->getHeaderLine('X-Test') === 'yes';
            }))
            ->willReturn($this->makeResponse(200, '{"id":1}'));

        $this->assertSame(
            ['id' => 1],
            $client->httpGet('/users', ['active' => 1, 'ignored' => null], true, false, ['X-Test' => 'yes'])
        );
    }

    public function testHttpGetReturnsNullForNoContentResponse(): void
    {
        $client = $this->createClient();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->makeResponse(204, ''));

        $this->assertNull($client->httpGet('/users'));
    }

    public function testHttpPostSendsJsonPayloadAndReturnsDecodedJson(): void
    {
        $client = $this->createClient(['Token' => 'secret-token']);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'POST'
                    && (string) $request->getUri() === 'https://api.example/users'
                    && $request->getHeaderLine('Token') === 'secret-token'
                    && $request->getHeaderLine('X-Test') === 'yes'
                    && (string) $request->getBody() === '{"name":"Alice"}';
            }))
            ->willReturn($this->makeResponse(201, '{"created":true}'));

        $this->assertSame(
            ['created' => true],
            $client->httpPost('/users', ['name' => 'Alice'], ['X-Test' => 'yes'])
        );
    }

    public function testHttpPutReturnsNullForNoContentResponse(): void
    {
        $client = $this->createClient();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'PUT'
                    && (string) $request->getUri() === 'https://api.example/users/1'
                    && (string) $request->getBody() === '{"name":"Bob"}';
            }))
            ->willReturn($this->makeResponse(204, ''));

        $this->assertNull($client->httpPut('/users/1', ['name' => 'Bob']));
    }

    public function testHttpPatchSendsPatchRequestAndReturnsDecodedJson(): void
    {
        $client = $this->createClient();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'PATCH'
                    && (string) $request->getUri() === 'https://api.example/users/1'
                    && (string) $request->getBody() === '{"enabled":true}';
            }))
            ->willReturn($this->makeResponse(200, '{"updated":true}'));

        $this->assertSame(['updated' => true], $client->httpPatch('/users/1', ['enabled' => true]));
    }

    public function testHttpDeleteSendsDeleteRequestAndReturnsNull(): void
    {
        $client = $this->createClient();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'DELETE'
                    && (string) $request->getUri() === 'https://api.example/users/1';
            }))
            ->willReturn($this->makeResponse(204, ''));

        $this->assertNull($client->httpDelete('/users/1'));
    }

    public function testBuildHeadersMergesDefaultsAnonymousModeAndTracerHeader(): void
    {
        $tracer = $this->createMock(Tracer::class);
        $tracer->expects($this->once())
            ->method('getTraceHeaderToForward')
            ->willReturn('Root=1;Parent=2;Sampled=1');

        $client = $this->createClient([
            'Token' => 'secret-token',
            'Accept-Language' => 'cy',
            'X-Null' => null,
        ], $tracer);

        $headers = $this->invokePrivateMethod($client, 'buildHeaders', [['X-Test' => 'yes'], true]);

        $this->assertSame('application/json, application/problem+json', $headers['Accept']);
        $this->assertSame('cy', $headers['Accept-Language']);
        $this->assertSame('application/json; charset=utf-8', $headers['Content-Type']);
        $this->assertSame('LPA-FRONT', $headers['User-Agent']);
        $this->assertSame('yes', $headers['X-Test']);
        $this->assertSame('Root=1;Parent=2;Sampled=1', $headers['X-Trace-Id']);
        $this->assertArrayNotHasKey('Token', $headers);
        $this->assertArrayNotHasKey('X-Null', $headers);
    }

    public function testHandleResponseReturnsDecodedArray(): void
    {
        $client = $this->createClient();

        $result = $this->invokePrivateMethod($client, 'handleResponse', [$this->makeResponse(200, '{"ok":true}')]);

        $this->assertSame(['ok' => true], $result);
    }

    public function testHandleResponseReturnsRawBodyWhenJsonDisabled(): void
    {
        $client = $this->createClient();

        $result = $this->invokePrivateMethod($client, 'handleResponse', [$this->makeResponse(200, 'plain-text'), false]);

        $this->assertSame('plain-text', $result);
    }

    public function testHandleResponseThrowsApiExceptionForMalformedJson(): void
    {
        $client = $this->createClient();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Malformed JSON response from server');

        $this->invokePrivateMethod($client, 'handleResponse', [$this->makeResponse(200, 'not-json')]);
    }

    public function testHandleErrorResponseLogsWarningAndThrowsApiException(): void
    {
        $client = $this->createClient();
        $response = $this->makeResponse(400, '{"detail":"bad request"}');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'API client error response',
                $this->callback(static function (array $context) use ($response): bool {
                    return $context['error_code'] === 'API_CLIENT_ERROR_RESPONSE'
                        && $context['status'] === 400
                        && $context['responseBody'] === ['detail' => 'bad request']
                        && $context['exception'] instanceof ApiException
                        && $context['exception']->getStatusCode() === 400;
                })
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('bad request');

        $this->invokePrivateMethod($client, 'handleErrorResponse', [$response]);
    }

    public function testHandleErrorResponseLogsErrorAndThrowsApiExceptionForServerError(): void
    {
        $client = $this->createClient();
        $response = $this->makeResponse(503, '{"detail":"server error"}');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'API client error response',
                $this->callback(static function (array $context): bool {
                    return $context['status'] === 503
                        && $context['responseBody'] === ['detail' => 'server error']
                        && $context['exception'] instanceof ApiException;
                })
            );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('server error');

        $this->invokePrivateMethod($client, 'handleErrorResponse', [$response]);
    }

    private function createClient(array $defaultHeaders = [], ?Tracer $tracer = null): Client
    {
        $client = new Client($this->httpClient, 'https://api.example', $defaultHeaders, $tracer);
        $client->setLogger($this->logger);

        return $client;
    }

    private function makeResponse(int $statusCode, string $body): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    private function invokePrivateMethod(Client $client, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($client, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($client, $arguments);
    }
}
