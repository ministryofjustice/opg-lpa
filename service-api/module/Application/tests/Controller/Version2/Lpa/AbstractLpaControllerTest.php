<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\AbstractService;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Mockery;
use Mockery\MockInterface;

class AbstractLpaControllerTest extends AbstractControllerTestCase
{
    /**
     * @var AbstractService|MockInterface
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->service = Mockery::mock(AbstractService::class);
    }

    public function testOnDispatchSuccess()
    {
        $result = $this->callOnDispatch(new TestableAbstractLpaController($this->authorizationService, $this->service));

        $this->assertNotNull($result);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testOnDispatchNoUserId()
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getParam')->withArgs(['userId'])->andReturn(null)->once();

        $mvcEvent = Mockery::mock(MvcEvent::class);
        $mvcEvent->shouldReceive('getRouteMatch')->andReturn($routeMatch)->once();

        $this->expectException(ApiProblemException::class);
        $this->expectExceptionMessage('User identifier missing from URL');

        $controller = new TestableAbstractLpaController($this->authorizationService, $this->service);
        $result = $controller->onDispatch($mvcEvent);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testOnDispatchUnathorised()
    {
        // Override expectations set in setUp
        $this->routeMatch->mockery_findExpectation('getParam', ['userId'])->andReturn(10)->once();
        $this->routeMatch->mockery_findExpectation('getParam', ['lpaId'])->andReturn(20)->once();
        $this->mvcEvent->mockery_findExpectation('getRequest', [])->andThrow(UnauthorizedException::class)->once();

        $controller = new TestableAbstractLpaController($this->authorizationService, $this->service);
        $result = $controller->onDispatch($this->mvcEvent);

        $this->assertNotNull($result);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals('{"type":"http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",' .
            '"title":"Unauthorized",' .
            '"status":401,' .
            '"detail":"Access Denied"}', $result->getContent());
    }

    public function testOnDispatchLocked()
    {
        // Override expectations set in setUp
        $this->routeMatch->mockery_findExpectation('getParam', ['userId'])->andReturn(10)->once();
        $this->routeMatch->mockery_findExpectation('getParam', ['lpaId'])->andReturn(20)->once();
        $this->mvcEvent->mockery_findExpectation('getRequest', [])->andThrow(LockedException::class)->once();

        $controller = new TestableAbstractLpaController($this->authorizationService, $this->service);
        $result = $controller->onDispatch($this->mvcEvent);

        $this->assertNotNull($result);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(
            '{"type":"http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",' .
            '"title":"Forbidden",' .
            '"status":403,' .
            '"detail":"LPA has been locked"}', $result->getContent());
    }
}
