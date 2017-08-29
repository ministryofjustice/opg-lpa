<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

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
    /**
     * @var MockInterface|Details
     */
    private $aboutYouDetails;
    private $postData = [

    ];

    public function setUp()
    {
        $this->controller = new AboutYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\AboutYou')->andReturn($this->form);

        $this->aboutYouDetails = Mockery::mock(Details::class);
        $this->serviceLocator->shouldReceive('get')->with('AboutYouDetails')->andReturn($this->aboutYouDetails);
    }

    public function testIndexActionGet()
    {
        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->with('user/about-you')->andReturn('user/about-you')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/about-you')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionPostInvalid()
    {
        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->url->shouldReceive('fromRoute')->with('user/about-you')->andReturn('user/about-you')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/about-you')->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionPostValid()
    {
        $response = new Response();

        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->with($this->form)->once();

        $this->flashMessenger->shouldReceive('addSuccessMessage')->with('Your details have been updated.')->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testNewActionGet()
    {
        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->with('user/about-you/new')->andReturn('user/about-you/new')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/about-you/new')->once();

        $result = $this->controller->newAction();

        $this->assertEquals($this->form, $result['form']);
        $this->assertEquals(null, $result['error']);
    }

    public function testNewActionPostValid()
    {
        $response = new Response();

        $user = new User();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->with($this->form)->once();

        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->newAction();

        $this->assertEquals($response, $result);
    }
}