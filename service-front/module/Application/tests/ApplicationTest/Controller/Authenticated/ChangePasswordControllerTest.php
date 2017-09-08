<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangePasswordController;
use Application\Form\User\ChangePassword;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class ChangePasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ChangePasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ChangePassword
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
        $this->controller = new ChangePasswordController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ChangePassword::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ChangePassword')->andReturn($this->form);

        $this->aboutYouDetails = Mockery::mock(Details::class);
        $this->serviceLocator->shouldReceive('get')->with('AboutYouDetails')->andReturn($this->aboutYouDetails);

        $this->user = FixturesData::getUser();
        $this->userDetailsSession->user = $this->user;
    }

    public function testIndexActionGet()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-password')->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-password')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-password')->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-password')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }

    public function testIndexActionPostValid()
    {
        $response = new Response();

        $this->url->shouldReceive('fromRoute')->with('user/change-password')->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-password')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->setPostValid($this->form, $this->postData);
        $this->aboutYouDetails->shouldReceive('updatePassword')->with($this->form)->andReturn(true)->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')->with('Your new password has been saved. Please remember to use this new password to sign in from now on.')->once();
        $this->redirect->shouldReceive('toRoute')->with('user/about-you')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionUpdateFailes()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-password')->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-password')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->setPostValid($this->form, $this->postData);
        $this->aboutYouDetails->shouldReceive('updatePassword')->with($this->form)->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }
}