<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\WhoAreYouController;
use Application\Library\Http\Response\Json;
use Application\Model\Service\WhoAreYou\Service;
use Mockery;
use Mockery\MockInterface;
use ZF\ApiProblem\ApiProblem;

class WhoAreYouControllerTest extends AbstractControllerTest
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(array $parameters = []) : WhoAreYouController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new WhoAreYouController($this->authorizationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testUpdateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data']])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update($this->lpaId, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data']])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->update($this->lpaId, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ], $response->toArray());
    }

    public function testUpdateUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data']])
            ->andReturn('unexpected type')->once();

        $response = $controller->update($this->lpaId, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ], $response->toArray());
    }

    /**
     * @expectedException ZfcRbac\Exception\UnauthorizedException
     * @expectedExceptionMessage You do not have permission to access this service
     */
    public function testUpdateUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->update(null, []);
    }
}
