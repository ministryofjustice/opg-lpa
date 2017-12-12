<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\PingController;
use Application\Model\Service\System\Status;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class PingControllerTest extends AbstractControllerTest
{
    /**
     * @var PingController
     */
    private $controller;
    /**
     * @var MockInterface|Status
     */
    private $status;
    private $checkResultOk = array (
        'dynamo' =>
            array (
                'ok' => true,
                'details' =>
                    array (
                        'sessions' => true,
                        'properties' => true,
                        'locks' => true,
                    ),
            ),
        'api' =>
            array (
                'ok' => true,
                'details' =>
                    array (
                        200 => true,
                        'database' =>
                            array (
                                'ok' => true,
                            ),
                        'auth' =>
                            array (
                                'ok' => true,
                                'details' =>
                                    array (
                                        200 => true,
                                        'ok' => true,
                                        'database' => true,
                                    ),
                            ),
                        'queue' =>
                            array (
                                'ok' => true,
                                'details' =>
                                    array (
                                        'available' => true,
                                        'length' => 0,
                                        'lengthAcceptable' => true,
                                    ),
                            ),
                        'ok' => true,
                    ),
            ),
        'auth' =>
            array (
                'ok' => true,
                'details' =>
                    array (
                        200 => true,
                        'ok' => true,
                        'database' => true,
                    ),
            ),
        'ok' => true,
        'iterations' => 6,
    );

    public function setUp()
    {
        $this->controller = new PingController();
        parent::controllerSetUp($this->controller);

        $this->status = Mockery::mock(Status::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['SiteStatus'])->andReturn($this->status);
    }

    public function testIndexAction()
    {
        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->checkResultOk, $result->getVariable('status'));
    }

    public function testJsonAction()
    {
        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var JsonModel $result */
        $result = $this->controller->jsonAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('unknown', $result->getVariable('commit'));
        $this->assertEquals('1.2.3.4-test', $result->getVariable('tag'));
    }

    public function testPingdomActionOk()
    {
        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var Response $result */
        $result = $this->controller->pingdomAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertContains('<pingdom_http_custom_check><status>OK</status>', $result->getContent());
    }

    public function testPingdomActionError()
    {
        $checkResultError = $this->checkResultOk;
        $checkResultError['ok'] = false;
        $this->status->shouldReceive('check')->andReturn($checkResultError)->once();

        /** @var Response $result */
        $result = $this->controller->pingdomAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertContains('<pingdom_http_custom_check><status>ERROR</status>', $result->getContent());
    }
}
