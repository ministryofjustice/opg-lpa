<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\VerifyEmailAddressController;
use ApplicationTest\Controller\AbstractControllerTest;

class VerifyEmailAddressControllerTest extends AbstractControllerTest
{
    /**
     * @var VerifyEmailAddressController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new VerifyEmailAddressController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}