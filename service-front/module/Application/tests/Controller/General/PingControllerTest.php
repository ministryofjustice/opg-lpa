<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\PingController;
use Application\Model\Service\System\Status;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\Http\Response;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class PingControllerTest extends MockeryTestCase
{
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

    protected function getController()
    {
        /** @var PingController $controller */
        $controller = new PingController();

        $this->status = Mockery::mock(Status::class);
        $controller->setStatusService($this->status);
        $controller->setConfig(['version' => ['tag' => '1.2.3.4-test']]);

        return $controller;
    }

    public function testIndexAction()
    {
        $controller = $this->getController();

        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->checkResultOk, $result->getVariable('status'));
    }

    public function testJsonAction()
    {
        $controller = $this->getController();

        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var JsonModel $result */
        $result = $controller->jsonAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('1.2.3.4-test', $result->getVariable('tag'));
    }

    public function testPingdomActionOk()
    {
        $controller = $this->getController();

        $this->status->shouldReceive('check')->andReturn($this->checkResultOk)->once();

        /** @var Response $result */
        $result = $controller->pingdomAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('<pingdom_http_custom_check><status>OK</status>', $result->getContent());
    }

    public function testPingdomActionError()
    {
        $controller = $this->getController();

        $checkResultError = $this->checkResultOk;
        $checkResultError['ok'] = false;
        $this->status->shouldReceive('check')->andReturn($checkResultError)->once();

        /** @var Response $result */
        $result = $controller->pingdomAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertStringContainsString('<pingdom_http_custom_check><status>ERROR</status>', $result->getContent());
    }
}
