<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\ForgotPasswordController;
use Application\Form\User\ConfirmEmail;
use Application\Form\User\SetPassword;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class ForgotPasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ForgotPasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ConfirmEmail
     */
    private $resetPasswordEmailForm;
    /**
     * @var MockInterface|SetPassword
     */
    private $setPasswordForm;
    private $postData = [
        'token' => 'unitTest',
        'email' => 'unit@test.com',
        'password' => 'newPassword'
    ];

    public function setUpController($setUpIdentity = true)
    {
        $this->controller = parent::controllerSetUp(ForgotPasswordController::class, $setUpIdentity);

        $this->resetPasswordEmailForm = Mockery::mock(ConfirmEmail::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\ConfirmEmail'])->andReturn($this->resetPasswordEmailForm);

        $this->setPasswordForm = Mockery::mock(SetPassword::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\SetPassword'])->andReturn($this->setPasswordForm);

        $this->userDetails = Mockery::mock(Details::class);
        $this->controller->setUserService($this->userDetails);
    }

    public function testIndexActionAlreadyLoggedIn()
    {
        $this->setUpController();

        $response = new Response();

        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionGet()
    {
        $this->setUpController(false);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->resetPasswordEmailForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionFormInvalid()
    {
        $this->setUpController(false);

        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->setPostInvalid($this->resetPasswordEmailForm, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->resetPasswordEmailForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionPostError()
    {
        $this->setUpController(false);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->resetPasswordEmailForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->resetPasswordEmailForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->resetPasswordEmailForm->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->userDetails->shouldReceive('requestPasswordResetEmail')->andReturn('Password reset failed');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/email-sent.twig', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
        $this->assertEquals(false, $result->getVariable('accountNotActivated'));
    }

    public function testIndexActionPostAccountNotActivated()
    {
        $this->setUpController(null);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->resetPasswordEmailForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->resetPasswordEmailForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->resetPasswordEmailForm->shouldReceive('getData')->andReturn($this->postData)->twice();
        $this->userDetails->shouldReceive('requestPasswordResetEmail')->andReturn('account-not-activated');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/email-sent.twig', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
        $this->assertEquals(true, $result->getVariable('accountNotActivated'));
    }

    public function testResetPasswordActionEmptyToken()
    {
        $this->setUpController();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $this->controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/invalid-reset-token.twig', $result->getTemplate());
    }

    public function testResetPasswordActionAlreadyLoggedIn()
    {
        $this->setUpController();

        $response = new Response();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])
            ->andReturn($response)->once();
        $this->sessionManager->shouldReceive('initialise')->once();

        $result = $this->controller->resetPasswordAction();

        $this->assertEquals($response, $result);
    }

    public function testResetPasswordActionGet()
    {
        $this->setUpController(false);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testResetPasswordActionPostInvalid()
    {
        $this->setUpController(false);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->setPostInvalid($this->setPasswordForm, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testResetPasswordActionPostError()
    {
        $this->setUpController(false);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->setPasswordForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->setPasswordForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->setPasswordForm->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->userDetails->shouldReceive('setNewPassword')
            ->withArgs([$this->postData['token'], $this->postData['password']])->andReturn('Password change failed');

        /** @var ViewModel $result */
        $result = $this->controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals('Password change failed', $result->getVariable('error'));
    }

    public function testResetPasswordActionPostInvalidToken()
    {
        $this->setUpController(false);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->setPasswordForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->setPasswordForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->setPasswordForm->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->userDetails->shouldReceive('setNewPassword')
            ->withArgs([$this->postData['token'], $this->postData['password']])->andReturn('invalid-token');

        /** @var ViewModel $result */
        $result = $this->controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/invalid-reset-token.twig', $result->getTemplate());
    }

    public function testResetPasswordActionPostSuccess()
    {
        $this->setUpController(false);

        $response = new Response();

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->setPasswordForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->setPasswordForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->setPasswordForm->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->userDetails->shouldReceive('setNewPassword')
            ->withArgs([$this->postData['token'], $this->postData['password']])->andReturn(true);
        $this->redirect->shouldReceive('toRoute')->withArgs(['login'])->andReturn($response)->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')->withArgs(['Password successfully reset'])->once();

        $result = $this->controller->resetPasswordAction();

        $this->assertEquals($response, $result);
    }
}
