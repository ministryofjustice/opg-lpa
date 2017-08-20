<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Form\Lpa\ReuseDetailsForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class ReuseDetailsControllerTest extends AbstractControllerTest
{
    /**
     * @var ReuseDetailsController
     */
    private $controller;
    /**
     * @var MockInterface|ReuseDetailsForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ReuseDetailsController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ReuseDetailsForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\ReuseDetailsForm')->andReturn($this->form);
    }
}