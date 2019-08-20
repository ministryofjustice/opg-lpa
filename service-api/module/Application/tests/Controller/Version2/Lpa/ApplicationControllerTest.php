<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\ApplicationController;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Applications\Service;
use Mockery;
use Mockery\MockInterface;
use ZF\ApiProblem\ApiProblem;

class ApplicationControllerTest extends AbstractControllerTest
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(Array $parameters = []) : ApplicationController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new ApplicationController($this->authorizationService, $this->service);
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

        $this->service->shouldReceive('fetch')->andReturn($this->createEntity());

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

    /**
     * @expectedException ZfcRbac\Exception\UnauthorizedException
     * @expectedExceptionMessage You do not have permission to access this service
     */
    public function testGetUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->get(10);
    }

    public function testGetListSuccessUnfiltered()
    {
        $controller = $this->getController();

        $entity = $this->createEntity([
            'key1' => 'value1',
            'key2' => 'value 2']);
        $entity->shouldReceive('setCurrentPageNumber')->withArgs([1])->once();

        $this->service->shouldReceive('fetchAll')->withArgs([$this->userId, []])->andReturn($entity);

        $response = $controller->getList();

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key1":"value1","key2":"value 2"}', $response->getContent());
    }

    public function testGetListSuccessPaged()
    {
        $controller = $this->getController(['page'=>2,'perPage'=>100,'misc'=>'value']);

        $entity = $this->createEntity([
            'key1' => 'value1',
            'key2' => 'value 2']);
        $entity->shouldReceive('setCurrentPageNumber')->withArgs([2])->once();
        $entity->shouldReceive('setItemCountPerPage')->withArgs([100])->once();

        $this->service->shouldReceive('fetchAll')->withArgs([$this->userId, ['misc'=>'value']])->andReturn($entity);

        $response = $controller->getList();

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key1":"value1","key2":"value 2"}', $response->getContent());
    }

    public function testGetListSuccessNoContent()
    {
        $controller = $this->getController();
        $this->service->shouldReceive('fetchAll')->withArgs([$this->userId, []])->andReturn(null);

        $response = $controller->getList();

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    /**
     * @expectedException ZfcRbac\Exception\UnauthorizedException
     * @expectedExceptionMessage You do not have permission to access this service
     */
    public function testGetListUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->getList();
    }

    public function testCreateSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([['some'=>'data'], $this->userId])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->create(['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testCreateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->withArgs([['some'=>'data'], $this->userId])
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

        $this->service->shouldReceive('create')->withArgs([['some'=>'data'], $this->userId])
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

    public function testPatchSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('patch')->withArgs([['some'=>'data'], 10, $this->userId])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->patch(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testPatchApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('patch')->withArgs([['some'=>'data'], 10, $this->userId])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->patch(10, ['some'=>'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals(Array (
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ), $response->toArray());

    }

    public function testPatchUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('patch')->withArgs([['some'=>'data'], 10, $this->userId])
            ->andReturn('unexpected type')->once();

        $response = $controller->patch(10, ['some'=>'data']);

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
    public function testPatchUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->patch(10, []);
    }

    public function testDeleteSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([10, $this->userId])
            ->andReturn(true)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testDeleteApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([10, $this->userId])
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

        $this->service->shouldReceive('delete')->withArgs([10, $this->userId])
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

    /**
     * @expectedException ZfcRbac\Exception\UnauthorizedException
     * @expectedExceptionMessage You do not have permission to access this service
     */
    public function testDeleteUnauthorised()
    {
        $this->setAuthorised(false);

        $controller = $this->getController();

        $controller->delete(10);
    }
}
