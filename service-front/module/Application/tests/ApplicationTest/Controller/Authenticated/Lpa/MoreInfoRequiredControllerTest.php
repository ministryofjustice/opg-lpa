<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\MoreInfoRequiredController;
use ApplicationTest\Controller\AbstractControllerTest;

class MoreInfoRequiredControllerTest extends AbstractControllerTest
{
    /**
     * @var MoreInfoRequiredController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new MoreInfoRequiredController();
        parent::controllerSetUp($this->controller);
    }
}