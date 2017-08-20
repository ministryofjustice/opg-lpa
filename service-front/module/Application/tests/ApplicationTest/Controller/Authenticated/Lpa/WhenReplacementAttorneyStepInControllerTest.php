<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController;
use Application\Form\Lpa\WhenReplacementAttorneyStepInForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class WhenReplacementAttorneyStepInControllerTest extends AbstractControllerTest
{
    /**
     * @var WhenReplacementAttorneyStepInController
     */
    private $controller;
    /**
     * @var MockInterface|WhenReplacementAttorneyStepInForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new WhenReplacementAttorneyStepInController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhenReplacementAttorneyStepInForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\WhenReplacementAttorneyStepInForm')->andReturn($this->form);
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