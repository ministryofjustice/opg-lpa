<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\LockController;
use Application\Model\Service\Lock\Service;
use Application\Library\Http\Response\Json;
use Mockery;
use Mockery\MockInterface;
use ZF\ApiProblem\ApiProblem;

class LockControllerTest extends AbstractControllerTest
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(Array $parameters = []) : LockController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new LockController($this->authorizationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testCreateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([$this->lpaId])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->create(['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testCreateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([$this->lpaId])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->create(['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ), $response->toArray());

    }

    public function testCreateUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([$this->lpaId])
            ->andReturn('unexpected type')->once();

        $response = $controller->create(['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ), $response->toArray());
    }

    /**
     * @expectedException ZfcRbac\Exception\UnauthorizedException
     * @expectedExceptionMessage You do not have permission to access this service
     */
    public function testCreateUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->create([]);
    }
}
