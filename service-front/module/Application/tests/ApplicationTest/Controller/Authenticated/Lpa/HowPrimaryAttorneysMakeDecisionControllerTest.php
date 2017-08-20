<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController;
use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class HowPrimaryAttorneysMakeDecisionControllerTest extends AbstractControllerTest
{
    /**
     * @var HowPrimaryAttorneysMakeDecisionController
     */
    private $controller;
    /**
     * @var MockInterface|HowAttorneysMakeDecisionForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new HowPrimaryAttorneysMakeDecisionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(HowAttorneysMakeDecisionForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\HowAttorneysMakeDecisionForm')->andReturn($this->form);
    }
}