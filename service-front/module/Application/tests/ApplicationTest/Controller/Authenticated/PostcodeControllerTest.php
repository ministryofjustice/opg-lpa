<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\PostcodeController;
use Application\Model\Service\AddressLookup\PostcodeInfo;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

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
    /**
     * @var MockInterface|PostcodeInfo
     */
    private $addressLookup;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(PostcodeController::class);

        $this->event = Mockery::mock(MvcEvent::class);
        $this->controller->setEvent($this->event);

        $this->routeMatch = Mockery::mock(RouteMatch::class);

        $this->addressLookup = Mockery::mock(PostcodeInfo::class);
        $this->controller->setAddressLookup($this->addressLookup);
    }

    public function testIndexActionPostcodeNotFound()
    {
        $this->params->shouldReceive('fromQuery')->withArgs(['postcode'])->andReturn(null)->once();
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch)->once();
        $this->event->shouldReceive('getResponse')->andReturn(new Response())->once();
        $this->routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->getVariable('content'));
    }

    public function testIndexActionSinglePostcode()
    {
        $address = [
            'line1' => '102 Petty France',
            'line2' => 'Westminster',
            'line3' => 'London',
            'postcode' => 'SW1H 9AJ',
            'description' => 'Ministry of Justice',
        ];

        $this->params->shouldReceive('fromQuery')->withArgs(['postcode'])->andReturn('SW1H 9AJ')->once();
        $this->addressLookup->shouldReceive('lookupPostcode')->withArgs(['SW1H 9AJ'])->andReturn([$address])->once();

        /** @var JsonModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('isPostcodeValid'));
        $this->assertEquals(true, $result->getVariable('success'));
        $this->assertEquals([[
//            'id' => $address['Id'],
            'description' => $address['description'],
            'line1' => $address['line1'],
            'line2' => $address['line2'],
            'line3' => $address['line3'],
            'postcode' => $address['postcode'],
        ]], $result->getVariable('addresses'));
    }
}
