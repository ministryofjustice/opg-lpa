<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CompleteController;
use ApplicationTest\Controller\AbstractControllerTest;
use RuntimeException;

class CompleteControllerTest extends AbstractControllerTest
{
    /**
     * @var CompleteController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new CompleteController();
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