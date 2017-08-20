<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\PingController;
use ApplicationTest\Controller\AbstractControllerTest;

class PingControllerTest extends AbstractControllerTest
{
    /**
     * @var PingController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new PingController();
        parent::controllerSetUp($this->controller);
    }
}