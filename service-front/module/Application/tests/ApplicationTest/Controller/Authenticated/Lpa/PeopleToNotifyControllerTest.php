<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\PeopleToNotifyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class PeopleToNotifyControllerTest extends AbstractControllerTest
{
    /**
     * @var PeopleToNotifyController
     */
    private $controller;
    /**
     * @var MockInterface|PeopleToNotifyForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new PeopleToNotifyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(PeopleToNotifyForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PeopleToNotifyForm')->andReturn($this->form);
    }
}