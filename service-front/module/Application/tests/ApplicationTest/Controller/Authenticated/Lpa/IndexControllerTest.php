<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\IndexController;
use ApplicationTest\Controller\AbstractControllerTest;

class IndexControllerTest extends AbstractControllerTest
{
    /**
     * @var IndexController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new IndexController();
        parent::controllerSetUp($this->controller);
    }
}