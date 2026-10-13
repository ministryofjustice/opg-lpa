<?php

declare(strict_types=1);

namespace ApplicationTest\Handle;

use Application\Handler\HealthCheckDependenciesHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class HealthCheckDependenciesHandlerTest extends MockeryTestCase
{
    private MockInterface&ClientInterface $httpClient;
    private MockInterface&LoggerInterface $logger;

    private HealthCheckDependenciesHandler $handler;

    public function setUp(): void
    {
        $this->httpClient         = Mockery::mock(ClientInterface::class);
        $this->logger             = Mockery::mock(LoggerInterface::class);

        $this->handler = new HealthCheckDependenciesHandler(
            $this->httpClient,
            $this->logger,
            ['discovery_url' => 'http://onelogin', 'user_agent' => 'us'],
        );
    }

    public function testItReturnsAllServiceOk(): void
    {
        $response = Mockery::mock(Response::class);
        $response->allows('getStatusCode')->andReturns(200);

        $this->httpClient->expects('sendRequest')
            ->with($this->callback(function (Request $r): bool {
                $this->assertEquals('GET', $r->getMethod());
                $this->assertEquals('http://onelogin', (string) $r->getUri());
                $this->assertEquals(['us'], $r->getHeader('User-Agent'));
                return true;
            }))
            ->andReturns($response);

        $expectedResult = [
            'ok' => true,
            'status' => Constants::STATUS_PASS,
            'oneLogin' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => 200,
                ],
            ],
        ];

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }
}
