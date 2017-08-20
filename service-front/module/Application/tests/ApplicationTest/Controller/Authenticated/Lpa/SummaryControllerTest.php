<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTest;
use RuntimeException;

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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }
}