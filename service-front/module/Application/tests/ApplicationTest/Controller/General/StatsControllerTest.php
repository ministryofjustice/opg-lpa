<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\StatsController;
use ApplicationTest\Controller\AbstractControllerTest;

class StatsControllerTest extends AbstractControllerTest
{
    /**
     * @var StatsController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new StatsController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}