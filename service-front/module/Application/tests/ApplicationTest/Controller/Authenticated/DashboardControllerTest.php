<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DashboardController;
use ApplicationTest\Controller\AbstractControllerTest;

class DashboardControllerTest extends AbstractControllerTest
{
    /**
     * @var DashboardController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new DashboardController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}