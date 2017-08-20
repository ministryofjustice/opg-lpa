<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AdminController;
use ApplicationTest\Controller\AbstractControllerTest;

class AdminControllerTest extends AbstractControllerTest
{
    /**
     * @var AdminController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new AdminController();
        parent::controllerSetUp($this->controller);
    }
}