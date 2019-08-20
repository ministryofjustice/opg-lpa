<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Library\Authorization\UnauthorizedException;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\AbstractService;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use ZF\ApiProblem\ApiProblemResponse;

class AbstractLpaControllerTest extends AbstractControllerTest
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

    /**
     * @expectedException Application\Library\ApiProblem\ApiProblemException
     * @expectedExceptionMessage User identifier missing from URL
     */
    public function testOnDispatchNoUserId()
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getParam')->withArgs(['userId'])->andReturn(null)->once();

        $mvcEvent = Mockery::mock(MvcEvent::class);
        $mvcEvent->shouldReceive('getRouteMatch')->andReturn($routeMatch)->once();

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
