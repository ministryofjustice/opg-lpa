<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangeEmailAddressController;
use Application\Form\User\ChangeEmailAddress;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use Laminas\View\Model\ViewModel;

final class ChangeEmailAddressControllerTest extends AbstractControllerTestCase
{
    private MockInterface|ChangeEmailAddress $form;
    private array $postData = [
        'email' => 'newunit@test.com'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(ChangeEmailAddress::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\ChangeEmailAddress'])->andReturn($this->form);

        $this->authenticationService->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
    }

    public function testIndexActionGet(): void
    {
        /** @var ChangeEmailAddressController $controller */
        $controller = $this->getController(ChangeEmailAddressController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentEmailAddress'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var ChangeEmailAddressController $controller */
        $controller = $this->getController(ChangeEmailAddressController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentEmailAddress'));
    }

    public function testIndexActionPostValid(): void
    {
        /** @var ChangeEmailAddressController $controller */
        $controller = $this->getController(ChangeEmailAddressController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->userDetails->shouldReceive('requestEmailUpdate')->once()->andReturn(true);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/change-email-address/email-sent.twig', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
    }

    public function testIndexActionPostUpdateFailed(): void
    {
        /** @var ChangeEmailAddressController $controller */
        $controller = $this->getController(ChangeEmailAddressController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('requestEmailUpdate')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentEmailAddress'));
    }
}
