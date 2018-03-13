<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangeEmailAddressController;
use Application\Form\User\ChangeEmailAddress;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\View\Model\ViewModel;

class ChangeEmailAddressControllerTest extends AbstractControllerTest
{
    /**
     * @var ChangeEmailAddressController
     */
    private $controller;
    /**
     * @var MockInterface|ChangeEmailAddress
     */
    private $form;
    private $postData = [
        'email' => 'newunit@test.com'
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(ChangeEmailAddressController::class);

        $this->form = Mockery::mock(ChangeEmailAddress::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\ChangeEmailAddress'])->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userDetailsSession->user = $this->user;
    }

    public function testIndexActionGet()
    {
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->authenticationService->shouldReceive('setAdapter')->withArgs([$this->authenticationAdapter])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentAddress'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->authenticationService->shouldReceive('setAdapter')->withArgs([$this->authenticationAdapter])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentAddress'));
    }

    public function testIndexActionPostValid()
    {
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->authenticationService->shouldReceive('setAdapter')->withArgs([$this->authenticationAdapter])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->aboutYouDetails->shouldReceive('requestEmailUpdate')->once()->andReturn(true);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/change-email-address/email-sent', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
    }

    public function testIndexActionPostUpdateFailed()
    {
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/change-email-address'])->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'user/change-email-address'])->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->withArgs([$this->user->email->address])->once();
        $this->authenticationService->shouldReceive('setAdapter')->withArgs([$this->authenticationAdapter])->once();
        $this->form->shouldReceive('setAuthenticationService')->withArgs([$this->authenticationService])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->aboutYouDetails->shouldReceive('requestEmailUpdate')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals($this->user->email, $result->getVariable('currentAddress'));
    }
}
