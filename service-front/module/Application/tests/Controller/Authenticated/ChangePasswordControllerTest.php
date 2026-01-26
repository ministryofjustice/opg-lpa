<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangePasswordController;
use Application\Form\User\ChangePassword;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class ChangePasswordControllerTest extends AbstractControllerTestCase
{
    private MockInterface|ChangePassword $form;
    private array $postData = [
        'password_current' => 'Abcd1234',
        'password'         => 'Abcd1234',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(ChangePassword::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\ChangePassword'])->andReturn($this->form);

        $this->authenticationService->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
    }

    public function testIndexActionGet(): void
    {
        /** @var ChangePasswordController $controller */
        $controller = $this->getController(ChangePasswordController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-password'])->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-password'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var ChangePasswordController $controller */
        $controller = $this->getController(ChangePasswordController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-password'])->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-password'])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }

    public function testIndexActionPostValid(): void
    {
        /** @var ChangePasswordController $controller */
        $controller = $this->getController(ChangePasswordController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-password'])->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-password'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->userDetails->shouldReceive('updatePassword')
            ->withArgs([$this->postData['password_current'], $this->postData['password']])
            ->andReturn(true)->once();

        $this->flashMessenger->shouldReceive('addSuccessMessage')->withArgs([
            'Your new password has been saved. Please remember to use this new password to sign in from now on.'
        ])->once();
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('user/about-you', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionUpdateFails(): void
    {
        /** @var ChangePasswordController $controller */
        $controller = $this->getController(ChangePasswordController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-password'])->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-password'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('updatePassword')
            ->withArgs([$this->postData['password_current'], $this->postData['password']])
            ->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }
}
