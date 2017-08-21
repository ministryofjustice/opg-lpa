<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\ForgotPasswordController;
use Application\Form\User\ResetPasswordEmail;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\User\PasswordReset;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class ForgotPasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ForgotPasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ResetPasswordEmail
     */
    private $form;
    /**
     * @var MockInterface|PasswordReset
     */
    private $passwordReset;
    private $postData = [
        'token' => 'unitTest',
        'email' => 'unit@test.com'
    ];

    public function setUp()
    {
        $this->controller = new ForgotPasswordController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ResetPasswordEmail::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ResetPasswordEmail')->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->passwordReset = Mockery::mock(PasswordReset::class);
        $this->serviceLocator->shouldReceive('get')->with('PasswordReset')->andReturn($this->passwordReset);
    }

    public function testIndexActionAlreadyLoggedIn()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionGet()
    {
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->with('forgot-password')->andReturn('forgot-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'forgot-password')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionFormInvalid()
    {
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->with('forgot-password')->andReturn('forgot-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'forgot-password')->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData);
        $this->form->shouldReceive('setData')->with($this->postData);
        $this->form->shouldReceive('isValid')->andReturn(false);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionPostError()
    {
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->with('forgot-password')->andReturn('forgot-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'forgot-password')->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->passwordReset->shouldReceive('requestPasswordResetEmail')->andReturn('Password reset failed');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('Password reset failed', $result->getVariable('error'));
    }
}