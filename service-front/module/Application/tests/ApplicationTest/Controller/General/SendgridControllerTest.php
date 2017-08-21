<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\SendgridController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class SendgridControllerTest extends AbstractControllerTest
{
    /**
     * @var SendgridController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new SendgridController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testBounceActionBlanckFromAddress()
    {
        /** @var Response $result */
        $result = $this->controller->bounceAction();

        $this->assertInstanceOf(Response::class, $result);
    }
}