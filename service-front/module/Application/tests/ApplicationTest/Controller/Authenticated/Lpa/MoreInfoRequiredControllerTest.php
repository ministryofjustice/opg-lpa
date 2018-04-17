<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\MoreInfoRequiredController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\ViewModel;

class MoreInfoRequiredControllerTest extends AbstractControllerTest
{
    /**
     * @var MoreInfoRequiredController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(MoreInfoRequiredController::class);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
    }
}
