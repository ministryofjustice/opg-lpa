<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\StatusesHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusesHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private StatusesHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->handler = new StatusesHandler(
            $this->lpaApplicationService,
        );
    }

    private function createRequest(string $lpaIds): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-ids' => $lpaIds]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testReturnsJsonResponseWithStatuses(): void
    {
        $lpaIds = '1,2,3';
        $request = $this->createRequest($lpaIds);

        $statuses = [
            '1' => ['found' => true, 'status' => 'completed'],
            '2' => ['found' => true, 'status' => 'pending'],
            '3' => ['found' => true, 'status' => 'draft'],
        ];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with($lpaIds)
            ->willReturn($statuses);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($statuses, json_decode((string) $response->getBody(), true));
    }

    public function testHandlesSingleLpaId(): void
    {
        $request = $this->createRequest('42');

        $statuses = ['42' => ['found' => true, 'status' => 'completed']];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with('42')
            ->willReturn($statuses);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($statuses, json_decode((string) $response->getBody(), true));
    }

    public function testInjectsFoundFalseForIdsAbsentFromApiResponse(): void
    {
        // API returns results for only some of the requested IDs (e.g. LPAs with no
        // OPG processing status yet are omitted). The handler must fill in the gaps
        // so the dashboard JS never receives `undefined` for a requested ID.
        $lpaIds = '1,2,3';
        $request = $this->createRequest($lpaIds);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with($lpaIds)
            ->willReturn([
                '1' => ['found' => true, 'status' => 'received'],
                // '2' and '3' absent from API response
            ]);

        $response = $this->handler->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('1', $body);
        $this->assertTrue($body['1']['found']);

        $this->assertArrayHasKey('2', $body);
        $this->assertFalse($body['2']['found']);

        $this->assertArrayHasKey('3', $body);
        $this->assertFalse($body['3']['found']);
    }

    public function testInjectsFoundFalseWhenApiReturnsEmptyArray(): void
    {
        $lpaIds = '10,20';
        $request = $this->createRequest($lpaIds);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with($lpaIds)
            ->willReturn([]);

        $response = $this->handler->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(['found' => false], $body['10']);
        $this->assertEquals(['found' => false], $body['20']);
    }

    public function testDoesNotOverwriteExistingEntries(): void
    {
        // If the API already returned a `found => false` entry, we must not clobber it.
        $lpaIds = '5';
        $request = $this->createRequest($lpaIds);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with($lpaIds)
            ->willReturn(['5' => ['found' => false]]);

        $response = $this->handler->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertFalse($body['5']['found']);
    }
}
