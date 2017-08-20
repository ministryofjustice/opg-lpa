<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\SendgridController;
use ApplicationTest\Controller\AbstractControllerTest;

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
}