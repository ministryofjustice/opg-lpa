<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\PingController;
use Application\Model\Service\System\Status;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
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

    public function setUp()
    {
        $this->controller = new PingController();
        parent::controllerSetUp($this->controller);

        $this->status = Mockery::mock(Status::class);
        $this->serviceLocator->shouldReceive('get')->with('SiteStatus')->andReturn($this->status);
    }

    public function testIndexAction()
    {
        $this->status->shouldReceive('check')->andReturn('ok')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('ok', $result->getVariable('status'));
    }
}