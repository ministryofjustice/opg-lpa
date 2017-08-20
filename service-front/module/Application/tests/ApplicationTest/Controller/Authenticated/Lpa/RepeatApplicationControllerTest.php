<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\RepeatApplicationController;
use Application\Form\Lpa\RepeatApplicationForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class RepeatApplicationControllerTest extends AbstractControllerTest
{
    /**
     * @var RepeatApplicationController
     */
    private $controller;
    /**
     * @var MockInterface|RepeatApplicationForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new RepeatApplicationController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(RepeatApplicationForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\RepeatApplicationForm')->andReturn($this->form);
    }
}