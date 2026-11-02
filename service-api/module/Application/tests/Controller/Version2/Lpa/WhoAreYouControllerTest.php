<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\WhoAreYouController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\WhoAreYou\Service;
use Application\Library\Authorization\UnauthorizedException;
use Mockery;
use Mockery\MockInterface;

class WhoAreYouControllerTest extends AbstractControllerTestCase
{
    private Service|MockInterface $service;

    public function getController(): WhoAreYouController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new WhoAreYouController($this->authenticationService, $this->service);
        $this->callDispatch($controller, []);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testUpdateSuccess(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'])
            ->andReturn($this->createEntity(['key' => 'value']))->once();

        $response = $controller->update($this->lpaId, ['some' => 'data']);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testUpdateApiProblemFromService(): void
    {
        $controller = $this->getController();

        $this->service->shouldReceive('update')->with($this->lpaId, self::IF_MATCH_VALUE, $this->userId, ['some' => 'data'])
            ->andReturn(new ApiProblem(500, 'error'))->once();

        $response = $controller->update($this->lpaId, ['some' => 'data']);

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

        $controller = $this->getController();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this service');

        $controller->update(null, []);
    }
}
