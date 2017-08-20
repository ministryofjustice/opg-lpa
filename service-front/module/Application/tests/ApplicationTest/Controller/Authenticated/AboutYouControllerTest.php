<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class AboutYouControllerTest extends AbstractControllerTest
{
    /**
     * @var AboutYouController
     */
    private $controller;
    /**
     * @var MockInterface|AboutYou
     */
    private $form;

    public function setUp()
    {
        $this->controller = new AboutYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\AboutYou')->andReturn($this->form);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}