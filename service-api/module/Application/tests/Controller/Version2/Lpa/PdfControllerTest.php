<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\PdfController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Pdfs\Service;
use Lmc\Rbac\Mvc\Exception\UnauthorizedException;
use Mockery;
use Mockery\MockInterface;

class PdfControllerTest extends AbstractControllerTestCase
{
    /**
     * @var Service|MockInterface
     */
    private $service;

    public function getController(array $parameters = []): PdfController
    {
        $this->service = Mockery::mock(Service::class);

        $controller = new PdfController($this->authorizationService, $this->service);
        $this->callDispatch($controller, $parameters);
        $this->callOnDispatch($controller);

        return $controller;
    }

    public function testGetSuccess()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')
            ->with('98765', 10, 'trace-id-123')
            ->andReturn(['key' => 'value'])
            ->once();

        $response = $controller->get(10);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Json::class, $response);
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testGetSuccessEmpty()
    {
        $controller = $this->getController();

        $this->service->shouldReceive('fetch')->andReturn([]);

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
}
