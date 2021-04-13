<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\UserController;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Users\Service;
use Mockery;
use Mockery\MockInterface;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use LmcRbacMvc\Exception\UnauthorizedException;

class UserControllerTest extends AbstractControllerTest
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(Array $parameters = []) : UserController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new UserController($this->authorizationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testGetSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testGetSuccessEmpty()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn($this->createEntity([]));

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testGetApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn(new ApiProblem(500, 'error'))
            ->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ), $response->toArray());

    }

    public function testGetUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn('unexpected type')->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ), $response->toArray());
    }

    public function testGetUnauthorised()
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->get(10);
    }

    public function testUpdateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([['some'=>'data'], 10])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([['some'=>'data'], 10])
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

        $this->service->shouldReceive('update')->withArgs([['some'=>'data'], 10])
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

        $this->service->shouldReceive('delete')->withArgs([10])
            ->andReturn(true)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testDeleteApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([10])
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

        $this->service->shouldReceive('delete')->withArgs([10])
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
