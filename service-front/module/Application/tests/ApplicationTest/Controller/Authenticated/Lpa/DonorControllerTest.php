<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DonorController;
use Application\Form\Lpa\DonorForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class DonorControllerTest extends AbstractControllerTest
{
    /**
     * @var DonorController
     */
    private $controller;
    /**
     * @var MockInterface|DonorForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new DonorController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(DonorForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\DonorForm')->andReturn($this->form);
    }
}