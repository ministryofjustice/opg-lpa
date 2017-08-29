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
    /**
     * @var MockInterface|Details
     */
    private $aboutYouDetails;
    private $postData = [
        'email' => 'newunit@test.com'
    ];

    public function setUp()
    {
        $this->controller = new ChangeEmailAddressController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ChangeEmailAddress::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ChangeEmailAddress')->andReturn($this->form);

        $this->aboutYouDetails = Mockery::mock(Details::class);
        $this->serviceLocator->shouldReceive('get')->with('AboutYouDetails')->andReturn($this->aboutYouDetails);

        $this->user = FixturesData::getUser();
        $this->userDetailsSession->user = $this->user;
    }

    public function testIndexActionGet()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-email-address')->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-email-address')->once();
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
        $this->assertEquals($this->user->email, $result->getVariable('currentAddress'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-email-address')->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-email-address')->once();
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
        $this->assertEquals($this->user->email, $result->getVariable('currentAddress'));
    }

    public function testIndexActionPostValid()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-email-address')->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-email-address')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->aboutYouDetails->shouldReceive('requestEmailUpdate')->andReturnUsing(function ($form, $emailConfirmCallback, $currentAddress, $userId) {
            //Exercise the anonymous functions as the concrete Register class would
            $emailConfirmCallback($userId, 'ValidToken');
            return true;
        })->once();
        $this->url->shouldReceive('fromRoute')->with('user/change-email-address/verify', ['token'=>'ValidToken'], ['force_canonical' => true])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/change-email-address/email-sent', $result->getTemplate());
        $this->assertEquals($this->postData['email'], $result->getVariable('email'));
    }

    public function testIndexActionPostUpdateFailed()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-email-address')->andReturn('user/change-email-address')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-email-address')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
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