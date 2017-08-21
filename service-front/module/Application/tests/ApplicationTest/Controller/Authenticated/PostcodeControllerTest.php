<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\PostcodeController;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class PostcodeControllerTest extends AbstractControllerTest
{
    /**
     * @var PostcodeController
     */
    private $controller;
    /**
     * @var MockInterface|MvcEvent
     */
    private $event;
    /**
     * @var MockInterface|RouteMatch
     */
    private $routeMatch;

    public function setUp()
    {
        $this->controller = new PostcodeController();
        parent::controllerSetUp($this->controller);

        $this->event = Mockery::mock(MvcEvent::class);
        $this->controller->setEvent($this->event);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
    }

    public function testIndexActionPostcodeNotFound()
    {
        $this->params->shouldReceive('fromQuery')->with('postcode')->andReturn(null)->once();
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch)->once();
        $this->routeMatch->shouldReceive('setParam')->with('action', 'not-found')->once();
        $this->pluginManager->shouldReceive('get')->with('createHttpNotFoundModel', null)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals(null, $result);
    }
}