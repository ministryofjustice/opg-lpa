<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\PostcodeController;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\MockInterface;

class PostcodeControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockInterface|MvcEvent
     */
    private $event;

    /**
     * @var MockInterface|OrdnanceSurvey
     */
    private $addressLookup;

    protected function getController(string $controllerName)
    {
        /** @var PostcodeController $controller */
        $controller = parent::getController($controllerName);

        $this->event = Mockery::mock(MvcEvent::class);
        $controller->setEvent($this->event);

        $this->routeMatch = Mockery::mock(RouteMatch::class);

        $this->addressLookup = Mockery::mock(OrdnanceSurvey::class);
        $controller->setAddressLookup($this->addressLookup);

        return $controller;
    }

    public function testIndexActionPostcodeNotFound()
    {
        $controller = $this->getController(PostcodeController::class);

        $this->params->shouldReceive('fromQuery')->withArgs(['postcode'])->andReturn(null)->once();
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch)->once();
        $this->event->shouldReceive('getResponse')->andReturn(new Response())->once();
        $this->routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->getVariable('content'));
    }

    public function testIndexActionSinglePostcode()
    {
        $controller = $this->getController(PostcodeController::class);

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
        $result = $controller->indexAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('isPostcodeValid'));
        $this->assertEquals(true, $result->getVariable('success'));
        $this->assertEquals([[
            'description' => $address['description'],
            'line1' => $address['line1'],
            'line2' => $address['line2'],
            'line3' => $address['line3'],
            'postcode' => $address['postcode'],
        ]], $result->getVariable('addresses'));
    }
}
