<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Form\Lpa\ReuseDetailsForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Required data missing when attempting to load the reuse details screen
     */
    public function testIndexActionRequiredDataMissing()
    {
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromQuery')->once();

        $this->controller->indexAction();
    }
}