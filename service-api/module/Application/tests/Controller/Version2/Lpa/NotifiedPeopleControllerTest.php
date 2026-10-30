<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\NotifiedPeopleController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\NotifiedPeople\Service;
use Application\Library\Authorization\UnauthorizedException;
use Mockery;
use Mockery\MockInterface;

class NotifiedPeopleControllerTest extends AbstractControllerTestCase
{
    private Service|MockInterface $service;

    public function getController(): NotifiedPeopleController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new NotifiedPeopleController($this->authenticationService, $this->service);
        $this->callDispatch($controller, []);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testCreateSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->create(['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testCreateApiProblemFromService(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('create')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->create(['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ApiProblem::class, $response);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error'
        ], $response->toArray());
    }

    public function testCreateUnauthorised(): void
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->create([]);
    }

    public function testUpdateSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'], 10)
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update(10, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'], 10)
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

    public function testUpdateUnauthorised(): void
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->update(10, []);
    }

    public function testDeleteSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, 10)
            ->andReturn(null)->once();

        $response = $controller->delete(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testDeleteApiProblemFromService(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('delete')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, 10)
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

    public function testDeleteUnauthorised(): void
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->delete(10);
    }
}
