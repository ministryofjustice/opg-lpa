<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\RegisterController;
use Application\Form\User\Registration;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\Referer;
use Zend\Http\Response;

class RegisterControllerTest extends AbstractControllerTest
{
    /**
     * @var RegisterController
     */
    private $controller;
    /**
     * @var MockInterface|Registration
     */
    private $form;

    public function setUp()
    {
        $this->controller = new RegisterController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(Registration::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\General\Registration')->andReturn($this->form);
    }

    public function testIndexActionRefererGovUk()
    {
        $response = new Response();
        $referer = new Referer();
        $referer->setUri('http://www.gov.uk');

        $this->request->shouldReceive('getHeader')->with('Referer')->andReturn($referer)->once();
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}