<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\InstructionsController;
use Application\Form\Lpa\InstructionsAndPreferencesForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class InstructionsControllerTest extends AbstractControllerTest
{
    /**
     * @var InstructionsController
     */
    private $controller;
    /**
     * @var MockInterface|InstructionsAndPreferencesForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new InstructionsController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(InstructionsAndPreferencesForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\InstructionsAndPreferencesForm')->andReturn($this->form);
    }
}