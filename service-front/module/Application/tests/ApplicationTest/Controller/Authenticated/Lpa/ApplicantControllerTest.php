<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ApplicantController;
use Application\Form\Lpa\ApplicantForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class ApplicantControllerTest extends AbstractControllerTest
{
    /**
     * @var ApplicantController
     */
    private $controller;
    /**
     * @var MockInterface|ApplicantForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ApplicantController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ApplicantForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\ApplicantForm')->andReturn($this->form);
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