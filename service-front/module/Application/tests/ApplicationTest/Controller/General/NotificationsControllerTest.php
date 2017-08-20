<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\NotificationsController;
use ApplicationTest\Controller\AbstractControllerTest;

class NotificationsControllerTest extends AbstractControllerTest
{
    /**
     * @var NotificationsController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new NotificationsController();
        parent::controllerSetUp($this->controller);
    }
}