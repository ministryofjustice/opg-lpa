<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\PostcodeController;
use ApplicationTest\Controller\AbstractControllerTest;

class PostcodeControllerTest extends AbstractControllerTest
{
    /**
     * @var PostcodeController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new PostcodeController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}