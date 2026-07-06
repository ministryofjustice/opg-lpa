<?php

declare(strict_types=1);

namespace AppTest\Service\Stats;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Stats\StatsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class StatsServiceTest extends TestCase
{
    private ApiClient&MockObject $apiClient;
    private StatsService $service;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->service = new StatsService($this->apiClient);
    }

    public function testGetApiStatsReturnsArrayFromApiClient(): void
    {
        $stats = ['users' => 10, 'applications' => 5];

        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/stats/all')
            ->willReturn($stats);

        $this->assertSame($stats, $this->service->getApiStats());
    }

    public function testGetApiStatsReturnsFalseWhenApiExceptionIsThrown(): void
    {
        $this->apiClient->method('httpGet')
            ->willThrowException($this->makeApiException(500, 'server error'));

        $this->assertFalse($this->service->getApiStats());
    }

    private function makeApiException(int $statusCode, string $message): ApiException
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"detail":"' . $message . '"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return new ApiException($response, $message);
    }
}
