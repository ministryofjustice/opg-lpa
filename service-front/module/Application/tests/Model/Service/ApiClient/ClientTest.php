<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\ApiClient;

use Http\Client\Exception;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Hamcrest\Matchers;
use Http\Client\HttpClient;
use MakeShared\Telemetry\Tracer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class ClientTest extends MockeryTestCase
{
    private HttpClient|MockInterface $httpClient;
    private ResponseInterface|MockInterface $response;
    private LoggerInterface|MockInterface $logger;
    private Client $client;

    public function setUp(): void
    {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->client = new Client($this->httpClient, 'base_url/', ['Token' => 'test token']);
        $this->client->setLogger($this->logger);
    }

    public function setUpRequest(
        $returnStatus = 200,
        $returnBody = '{"test": "value"}',
        $verb = 'GET',
        $url = 'base_url/path',
        $requestData = null,
        $token = 'test token',
        $additionalHeaders = [],
    ): void {
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->response->shouldReceive('getStatusCode')->once()->andReturn($returnStatus);

        if ($returnBody !== null) {
            $mockBody = Mockery::mock(StreamInterface::class);
            $mockBody->shouldReceive('__toString')->once()->andReturn($returnBody);
            $this->response->shouldReceive('getBody')->once()->andReturn($mockBody);
        }

        $expectedHeaders = [
            'Accept' => 'application/json, application/problem+json',
            'Accept-Language' => 'en',
            'Content-Type' => 'application/json; charset=utf-8',
            'User-Agent' => 'LPA-FRONT',
            'Token' => $token,
        ] + $additionalHeaders;

        if ($requestData == null) {
            $this->httpClient->shouldReceive('sendRequest')
                ->withArgs(
                    [Matchers::equalTo(
                        new Request(
                            $verb,
                            new Uri($url),
                            $expectedHeaders,
                        )
                    )]
                )
                ->once()
                ->andReturn($this->response);
        } else {
            // withArgs is having to be omitted where a body is sent due to the streamsin the Request being a different
            // size
            $this->httpClient->shouldReceive('sendRequest')
                ->once()
                ->andReturn($this->response);
        }
    }

    /**
     *  As the token is private, test that it is updated indirectly by seeing what is added to a request header
     * @throws Exception
     */
    public function testUpdateToken(): void
    {
        $this->client->updateToken('new token');

        $this->setUpRequest(
            200,
            '{"test": "value"}',
            'GET',
            'base_url/path',
            null,
            'new token'
        );

        $this->client->httpGet('path');
    }

    /**
     * @throws Exception
     */
    public function testHttpGet(): void
    {
        $this->setUpRequest();

        $result = $this->client->httpGet('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @throws Exception
     */
    public function testHttpGetWithQuery(): void
    {
        $this->setUpRequest(200, '{"test":"value"}', 'GET', 'base_url/path?a=1');

        $result = $this->client->httpGet('path', ['a' => '1']);

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @throws Exception
     */
    public function testHttpGetJsonFalse(): void
    {
        $this->setUpRequest();

        $result = $this->client->httpGet('path', [], false);

        $this->assertEquals('{"test": "value"}', $result);
    }

    /**
     * @throws Exception
     */
    public function testHttpGetNoContent(): void
    {
        $this->setUpRequest(204, null);

        $result = $this->client->httpGet('path');

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testHttpGetNotFound(): void
    {
        $this->setUpRequest(404, '');
        $this->response->shouldReceive('getStatusCode')
            ->twice()
            ->andReturn(404);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(404, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:404 - Unexpected API response');


        $this->client->httpGet('path');
    }

    /**
     * @throws Exception
     */
    public function testHttpError(): void
    {
        $this->setUpRequest(500, 'An error');
        $this->response->shouldReceive('getStatusCode')
            ->twice()
            ->andReturn(500);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(500, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:500 - Unexpected API response');

        $this->client->httpGet('path');
    }

    public function testHttpDelete(): void
    {
        $this->setUpRequest(204, null, 'DELETE');

        $result = $this->client->httpDelete('path');

        $this->assertNull($result);
    }

    public function testHttpDeleteError(): void
    {
        $this->setUpRequest(500, 'An error', 'DELETE');
        $this->response->shouldReceive('getStatusCode')
            ->twice()
            ->andReturn(500);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(500, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:500 - Unexpected API response');

        $this->client->httpDelete('path');
    }

    public function testHttpPatch(): void
    {
        $this->setUpRequest(
            200,
            '{"test": "value"}',
            'PATCH',
            'base_url/path',
            '{"a":1}'
        );

        $result = $this->client->httpPatch('path', ['a' => 1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPatchAccept(): void
    {
        $this->setUpRequest(
            201,
            '{"test": "value"}',
            'PATCH',
            'base_url/path',
            '{"a":1}'
        );

        $result = $this->client->httpPatch('path', ['a' => 1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPatchError(): void
    {
        $this->setUpRequest(500, 'An error', 'PATCH', 'base_url/path', '{"a":1}');
        $this->response->shouldReceive('getStatusCode')->twice()->andReturn(500);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(500, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:500 - Unexpected API response');

        $this->client->httpPatch('path');
    }

    public function testHttpPost(): void
    {
        $this->setUpRequest(200, '{"test": "value"}', 'POST', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPost('path', ['a' => 1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPostAccept(): void
    {
        $this->setUpRequest(201, '{"test": "value"}', 'POST', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPost('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPostError(): void
    {
        $this->setUpRequest(500, 'An error', 'POST', 'base_url/path', '{"a":1}');
        $this->response->shouldReceive('getStatusCode')
            ->twice()
            ->andReturn(500);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(500, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:500 - Unexpected API response');

        $this->client->httpPost('path');
    }

    public function testHttpPut(): void
    {
        $this->setUpRequest(200, '{"test": "value"}', 'PUT', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPut('path', []);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPutAccept(): void
    {
        $this->setUpRequest(201, '{"test": "value"}', 'PUT', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPut('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPutError(): void
    {
        $this->setUpRequest(500, 'An error', 'PUT', 'base_url/path', '{"a":1}');

        $this->response->shouldReceive('getStatusCode')
            ->twice()
            ->andReturn(500);

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame('API client error response', $message);
                $this->assertSame(500, $context['status']);
                $this->assertArrayHasKey('exception', $context);
                $this->assertInstanceOf(ApiException::class, $context['exception']);

                return true;
            });

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:500 - Unexpected API response');

        $this->client->httpPut('path');
    }

    public function testXTraceIdHeaderIsSet(): void
    {
        $mockTracer = Mockery::mock(Tracer::class);
        $mockTracer->shouldReceive('getTraceHeaderToForward')->andReturn('Root=X;Parent=Y;Sampled=1');

        $client = new Client(
            $this->httpClient,
            'base_url/',
            ['Token' => 'test token'],
            $mockTracer,
        );

        $this->setUpRequest(
            200,
            '{"test": "value"}',
            'GET',
            'base_url/path',
            null,
            'test token',
            ['X-Trace-Id' => 'Root=X;Parent=Y;Sampled=1'],
        );

        $result = $client->httpGet('path');

        $this->assertEquals(['test' => 'value'], $result);
    }
}
