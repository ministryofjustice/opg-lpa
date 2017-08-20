<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\MoreInfoRequiredController;
use ApplicationTest\Controller\AbstractControllerTest;
use RuntimeException;

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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }
}