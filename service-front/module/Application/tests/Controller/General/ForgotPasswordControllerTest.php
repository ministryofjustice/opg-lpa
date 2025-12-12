<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\ForgotPasswordController;
use Application\Form\User\ConfirmEmail;
use Application\Form\User\SetPassword;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class ForgotPasswordControllerTest extends AbstractControllerTestCase
{
    private MockInterface|ConfirmEmail $resetPasswordEmailForm;
    private MockInterface|SetPassword $setPasswordForm;
    private array $postData = [
        'token' => 'unitTest',
        'email' => 'unit@test.com',
        'password' => 'newPassword'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->resetPasswordEmailForm = Mockery::mock(ConfirmEmail::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\ConfirmEmail'])->andReturn($this->resetPasswordEmailForm);

        $this->setPasswordForm = Mockery::mock(SetPassword::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\SetPassword'])->andReturn($this->setPasswordForm);

        $this->userDetails = Mockery::mock(Details::class);
    }

    protected function getController(string $controllerName)
    {
        /** @var ForgotPasswordController $controller */
        $controller = parent::getController($controllerName);

        $controller->setUserService($this->userDetails);

        return $controller;
    }

    public function testIndexActionAlreadyLoggedIn(): void
    {
        $controller = $this->getController(ForgotPasswordController::class);

        $response = new Response();

        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionGet(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->resetPasswordEmailForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionFormInvalid(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->setPostInvalid($this->resetPasswordEmailForm, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->resetPasswordEmailForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testIndexActionPostError(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->resetPasswordEmailForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->resetPasswordEmailForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->resetPasswordEmailForm->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->userDetails->shouldReceive('requestPasswordResetEmail')->andReturn('Password reset failed');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/email-sent.twig', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
        $this->assertEquals(false, $result->getVariable('accountNotActivated'));
    }

    public function testIndexActionPostAccountNotActivated(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['forgot-password'])->andReturn('forgot-password')->once();
        $this->resetPasswordEmailForm->shouldReceive('setAttribute')->withArgs(['action', 'forgot-password'])->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->resetPasswordEmailForm->shouldReceive('setData')->withArgs([$this->postData])->once();
        $this->resetPasswordEmailForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->resetPasswordEmailForm->shouldReceive('getData')->andReturn($this->postData)->twice();
        $this->userDetails->shouldReceive('requestPasswordResetEmail')->andReturn('account-not-activated');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/email-sent.twig', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
        $this->assertEquals(true, $result->getVariable('accountNotActivated'));
    }

    public function testResetPasswordActionEmptyToken(): void
    {
        $controller = $this->getController(ForgotPasswordController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/invalid-reset-token.twig', $result->getTemplate());
    }

    public function testResetPasswordActionAlreadyLoggedIn(): void
    {
        $this->params
            ->shouldReceive('fromRoute')
            ->withArgs(['token'])
            ->andReturn($this->postData['token'])
            ->once();

        $response = new Response();
        $this->redirect
            ->shouldReceive('toRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])
            ->andReturn($response)
            ->once();

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(true)
            ->once();

        $controller = $this->getController(ForgotPasswordController::class);

        $result = $controller->resetPasswordAction();
        $this->assertSame($response, $result);
    }


    public function testResetPasswordActionGet(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testResetPasswordActionPostInvalid(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn($this->postData['token'])->once();
        $url = 'forgot-password/callback?token=' . $this->postData['token'];
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['forgot-password/callback', ['token' => $this->postData['token']]])->andReturn($url)->once();
        $this->setPasswordForm->shouldReceive('setAttribute')->withArgs(['action', $url])->once();
        $this->setPostInvalid($this->setPasswordForm, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
    }

    public function testResetPasswordActionPostError(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

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
        $result = $controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->setPasswordForm, $result->getVariable('form'));
        $this->assertEquals('Password change failed', $result->getVariable('error'));
    }

    public function testResetPasswordActionPostInvalidToken(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

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
        $result = $controller->resetPasswordAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/general/forgot-password/invalid-reset-token.twig', $result->getTemplate());
    }

    public function testResetPasswordActionPostSuccess(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(ForgotPasswordController::class);

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

        $result = $controller->resetPasswordAction();

        $this->assertEquals($response, $result);
    }
}
