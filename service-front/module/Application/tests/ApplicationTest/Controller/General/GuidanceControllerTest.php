<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\GuidanceController;
use ApplicationTest\Controller\AbstractControllerTest;

class GuidanceControllerTest extends AbstractControllerTest
{
    /**
     * @var GuidanceController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new GuidanceController();
        parent::controllerSetUp($this->controller);
    }
}