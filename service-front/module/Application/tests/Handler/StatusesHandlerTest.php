<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\StatusesHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Router\RouteMatch;
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

    private function createRequest(array $routeParams = []): ServerRequest
    {
        $routeMatch = new RouteMatch($routeParams);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch);
    }

    public function testReturnsJsonResponseWithStatuses(): void
    {
        $lpaIds = '1,2,3';
        $request = $this->createRequest(['lpa-ids' => $lpaIds]);

        $statuses = [
            '1' => ['status' => 'completed'],
            '2' => ['status' => 'pending'],
            '3' => ['status' => 'draft'],
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
        $request = $this->createRequest(['lpa-ids' => '42']);

        $statuses = ['42' => ['status' => 'completed']];

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getStatuses')
            ->with('42')
            ->willReturn($statuses);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
