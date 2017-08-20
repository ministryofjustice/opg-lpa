<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DeleteController;
use ApplicationTest\Controller\AbstractControllerTest;

class DeleteControllerTest extends AbstractControllerTest
{
    /**
     * @var DeleteController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new DeleteController();
        parent::controllerSetUp($this->controller);
    }
}