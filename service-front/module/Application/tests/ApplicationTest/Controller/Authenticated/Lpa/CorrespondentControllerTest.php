<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CorrespondentController;
use Application\Form\Lpa\CorrespondentForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class CorrespondentControllerTest extends AbstractControllerTest
{
    /**
     * @var CorrespondentController
     */
    private $controller;
    /**
     * @var MockInterface|CorrespondentForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new CorrespondentController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(CorrespondentForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CorrespondentForm')->andReturn($this->form);
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