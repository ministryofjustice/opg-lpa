<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\HomeController;
use ApplicationTest\Controller\AbstractControllerTest;

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
        $this->controller->indexAction();
    }
}