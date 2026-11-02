<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\SeedController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Seed\Service;
use Application\Library\Authorization\UnauthorizedException;
use Mockery;
use Mockery\MockInterface;

class SeedControllerTest extends AbstractControllerTestCase
{
    private Service|MockInterface $service;

    public function getController(): SeedController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new SeedController($this->authenticationService, $this->service);
        $this->callDispatch($controller, []);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testGetSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testGetSuccessEmpty(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn($this->createEntity([]));

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(NoContent::class, $response);
    }

    public function testGetApiProblemFromService(): void
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

    public function testGetUnauthorised(): void
    {
        $this->setAuthorised(false);
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller = $this->getController();
        $controller->get(10);
    }

    public function testUpdateSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, ['some' => 'data'], self::IF_MATCH_VALUE, $this->userId)
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update(10, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, ['some' => 'data'], self::IF_MATCH_VALUE, $this->userId)
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
}
