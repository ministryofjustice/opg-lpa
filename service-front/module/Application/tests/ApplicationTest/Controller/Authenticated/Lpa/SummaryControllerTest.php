<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTest;

class SummaryControllerTest extends AbstractControllerTest
{
    /**
     * @var SummaryController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new SummaryController();
        parent::controllerSetUp($this->controller);
    }
}