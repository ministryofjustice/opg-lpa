<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\HomeController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\ViewModel;

class HomeControllerTest extends AbstractControllerTest
{
    /**
     * @var HomeController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new HomeController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(82, $result->getVariable('lpaFee'));
        $this->assertEquals('1.2.3.4-test', $result->getVariable('dockerTag'));
    }
}