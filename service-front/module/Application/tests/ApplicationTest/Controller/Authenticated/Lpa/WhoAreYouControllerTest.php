<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhoAreYouController;
use Application\Form\Lpa\WhoAreYouForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class WhoAreYouControllerTest extends AbstractControllerTest
{
    /**
     * @var WhoAreYouController
     */
    private $controller;
    /**
     * @var MockInterface|WhoAreYouForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new WhoAreYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhoAreYouForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\WhoAreYouForm')->andReturn($this->form);
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