<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangeEmailAddressController;
use Application\Form\User\ChangeEmailAddress;
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

    public function setUp()
    {
        $this->controller = new ChangeEmailAddressController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ChangeEmailAddress::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ChangeEmailAddress')->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userDetailsSession->user = $this->user;
    }

    public function testIndexAction()
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
}