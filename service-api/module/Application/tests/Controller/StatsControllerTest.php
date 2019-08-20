<?php

namespace ApplicationTest\Controller;

use Application\Controller\StatsController;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Stats\Service as StatsService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;
use ZF\ApiProblem\ApiProblem;

class StatsControllerTest extends MockeryTestCase
{
    /**
     * @var StatsController
     */
    private $controller;

    /**
     * @var StatsService|MockInterface
     */
    private $statsService;

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp()
    {
        $this->statsService = Mockery::mock(StatsService::class);

        $this->controller = new StatsController($this->statsService);
    }

    public function testGetSuccess()
    {
        $id = 123;

        $fetchRes = [
            'result' => true,
        ];

        $this->statsService->shouldReceive('fetch')
            ->with($id)
            ->andReturn($fetchRes);

        /** @var JsonResponse $result */
        $result = $this->controller->get($id);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(json_encode($fetchRes), $result->getContent());
    }

    public function testGetFailedApiProblem()
    {
        $id = 123;

        $fetchRes = new ApiProblem(500, 'failed');

        $this->statsService->shouldReceive('fetch')
            ->with($id)
            ->andReturn($fetchRes);

        /** @var ApiProblem $result */
        $result = $this->controller->get($id);

        $this->assertEquals($fetchRes, $result);
    }

    public function testGetFailedNoContentResponse()
    {
        $id = 123;

        $this->statsService->shouldReceive('fetch')
            ->with($id)
            ->andReturn([]);

        /** @var NoContentResponse $result */
        $result = $this->controller->get($id);

        $this->assertInstanceOf(NoContentResponse::class, $result);
    }

    public function testGetFailedApiProblem2()
    {
        $id = 123;

        $this->statsService->shouldReceive('fetch')
            ->with($id)
            ->andReturnNull();

        $result = $this->controller->get($id);

        $expected = new ApiProblem(500, 'Unable to process request');

        $this->assertEquals($expected, $result);
    }
}
