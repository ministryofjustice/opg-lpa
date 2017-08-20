<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\FeeReductionController;
use Application\Form\Lpa\FeeReductionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class FeeReductionControllerTest extends AbstractControllerTest
{
    /**
     * @var FeeReductionController
     */
    private $controller;
    /**
     * @var MockInterface|FeeReductionForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new FeeReductionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(FeeReductionForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\FeeReductionForm')->andReturn($this->form);
    }
}