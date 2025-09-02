<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\PrimaryAttorneyController;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\AttorneysPrimary\Service;
use Mockery;
use Mockery\MockInterface;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use LmcRbacMvc\Exception\UnauthorizedException;

class PrimaryAttorneyControllerTest extends AbstractControllerTestCase
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(Array $parameters = []) : PrimaryAttorneyController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new PrimaryAttorneyController($this->authorizationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testCreateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([$this->lpaId, ['some'=>'data']])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->create(['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testCreateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([$this->lpaId, ['some'=>'data']])
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

        $this->service->shouldReceive('create')->withArgs([$this->lpaId, ['some'=>'data']])
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

    public function testCreateUnauthorised()
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->create([]);
    }

    public function testUpdateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data'], 10])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data'], 10])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->update(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ), $response->toArray());

    }

    public function testUpdateUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([$this->lpaId, ['some'=>'data'], 10])
            ->andReturn('unexpected type')->once();

        $response = $controller->update(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ), $response->toArray());
    }

    public function testUpdateUnauthorised()
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->update(10, []);
    }

    public function testDeleteSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([$this->lpaId, 10])
            ->andReturn(true)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testDeleteApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([$this->lpaId, 10])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ), $response->toArray());
    }

    public function testDeleteFailed()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([$this->lpaId, 10])
            ->andReturn(false)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ), $response->toArray());
    }

    public function testDeleteUnauthorised()
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();

        $controller->delete(10);
    }
}
