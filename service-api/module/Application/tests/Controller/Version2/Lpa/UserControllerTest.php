<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\UserController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Authentication\Identity\User as UserIdentity;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Users\Service;
use Lmc\Rbac\Mvc\Exception\UnauthorizedException;
use Lmc\Rbac\Mvc\Service\AuthorizationService;
use Mockery;
use Mockery\MockInterface;

class UserControllerTest extends AbstractControllerTestCase
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(array $parameters = []): UserController
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

    public function testGetSuccessWithUserIdentity()
    {
        $service = Mockery::mock(Service::class);
        $service->shouldReceive('fetch')->andReturn($this->createEntity(['key' => 'value']));

        $identity = Mockery::mock(UserIdentity::class);
        $identity->shouldReceive('email')->andReturn('identity@email.address');

        $authorizationService = Mockery::mock(AuthorizationService::class);
        $authorizationService->shouldReceive('isGranted')->withArgs(['authenticated'])
            ->andReturn(true);
        $authorizationService->shouldReceive('isGranted')
            ->withArgs(['isAuthorizedToManageUser', $this->userId])
            ->andReturn(false);
        $authorizationService->shouldReceive('isGranted')
            ->withArgs(['admin'])
            ->andReturn(true);
        $authorizationService->shouldReceive('getIdentity')->andReturn($identity);

        $controller = new UserController($authorizationService, $service);
        $this->callDispatch($controller);
        $this->callOnDispatch($controller);

        $response = $controller->get(10);

        $this->assertInstanceOf(Json::class, $response);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('identity@email.address', $responseArray['email']['address']);
    }

    public function testGetApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn(new ApiProblem(500, 'error'))
            ->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ], $response->toArray());
    }

    public function testGetUnexpectedResponse()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn('unexpected type')->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ], $response->toArray());
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

        $this->service->shouldReceive('update')->withArgs([['some' => 'data'], 10])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update(10, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->withArgs([['some' => 'data'], 10])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->update(10, ['some' => 'data']);

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

        $this->service->shouldReceive('update')->withArgs([['some' => 'data'], 10])
            ->andReturn('unexpected type')->once();

        $response = $controller->update(10, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ], $response->toArray());
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
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ], $response->toArray());
    }

    public function testDeleteFailed()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->withArgs([10])
            ->andReturn(false)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Unable to process request'
        ], $response->toArray());
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
