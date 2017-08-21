<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Authentication\Result;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;

class AuthControllerTest extends AbstractControllerTest
{
    /**
     * @var AuthController
     */
    private $controller;
    /**
     * @var User
     */
    private $identity;
    /**
     * @var MockInterface|Login
     */
    private $form;
    private $postData = [
        'email' => 'unit@test.com',
        'password' => 'unitTest'
    ];

    public function setUp()
    {
        $this->controller = new AuthController();
        parent::controllerSetUp($this->controller);

        $this->identity = Mockery::mock(User::class);

        $this->request->shouldReceive('getMethod')->andReturn('POST');
        $this->request->shouldReceive('isPost')->andReturn(true);
        $this->request->shouldReceive('getPost')->andReturn($this->postData);

        $this->form = Mockery::mock(Login::class);
        $this->form->shouldReceive('setAttribute')->with('action', 'login');
        $this->form->shouldReceive('setData')->with($this->postData);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\Login')->andReturn($this->form);

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null);

        $this->url->shouldReceive('fromRoute')->with('login')->andReturn('login');

        $this->storage->shouldReceive('clear');

        $this->sessionManager->shouldReceive('initialise');
    }

    public function testIndexActionFormInvalid()
    {
        $this->form->shouldReceive('isValid')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionFormAuthenticationFailed()
    {
        $authenticationResult = new Result(0, null, ['Authentication Failed']);

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->postData['email'])->andReturn($this->authenticationAdapter)->once();
        $this->authenticationAdapter->shouldReceive('setPassword')->with($this->postData['password'])->once();

        $this->authenticationService->shouldReceive('authenticate')->with($this->authenticationAdapter)->andReturn($authenticationResult)->once();

        $this->form->shouldReceive('setData')->with(['email' => $this->postData['email']])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('Authentication Failed', $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionFormAuthenticationSuccessfulDashboard()
    {
        $authenticationResult = new Result(1, null);
        $response = new Response();

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->postData['email'])->andReturn($this->authenticationAdapter)->once();
        $this->authenticationAdapter->shouldReceive('setPassword')->with($this->postData['password'])->once();

        $this->authenticationService->shouldReceive('authenticate')->with($this->authenticationAdapter)->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->with(true)->once();

        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirect()
    {
        $authenticationResult = new Result(1, null);
        $response = new Response();

        $this->setPreAuthRequestUrl('https://localhost/user/about-you');

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->postData['email'])->andReturn($this->authenticationAdapter)->once();
        $this->authenticationAdapter->shouldReceive('setPassword')->with($this->postData['password'])->once();

        $this->authenticationService->shouldReceive('authenticate')->with($this->authenticationAdapter)->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->with(true)->once();

        $this->redirect->shouldReceive('toUrl')->with('https://localhost/user/about-you')->andReturn($response)->once();

        /** @var ViewModel $result */
        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->indexAction();
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirectLpa()
    {
        $authenticationResult = new Result(1, null);
        $response = new Response();

        $this->setPreAuthRequestUrl('https://localhost/lpa/3503563157/when-lpa-starts#current');

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->postData['email'])->andReturn($this->authenticationAdapter)->once();
        $this->authenticationAdapter->shouldReceive('setPassword')->with($this->postData['password'])->once();

        $this->authenticationService->shouldReceive('authenticate')->with($this->authenticationAdapter)->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->with(true)->once();

        $lpa = new Lpa();
        $lpa->id = 3503563157;
        $this->lpaApplicationService->shouldReceive('getApplication')->with($lpa->id)->andReturn($lpa);

        $this->redirect->shouldReceive('toRoute')->with('lpa/form-type', ['lpa-id' => $lpa->id], [])->andReturn($response)->once();

        /** @var ViewModel $result */
        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->indexAction();
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }

    public function testLogoutAction()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->with(['clear_storage'=>true])->once();
        $this->redirect->shouldReceive('toUrl')->with('https://www.gov.uk/done/lasting-power-of-attorney')->andReturn($response)->once();

        $result = $this->controller->logoutAction();

        $this->assertEquals($response, $result);
    }

    public function testDeletedAction()
    {
        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->with(['clear_storage'=>true])->once();

        $result = $this->controller->deletedAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    private function setPreAuthRequestUrl($url)
    {
        $this->sessionManager->shouldReceive('start')->once();
        $this->storage->shouldReceive('offsetExists')->with('PreAuthRequest')->andReturn(true);
        $preAuthRequest = new ArrayObject(['url' => $url]);
        $this->storage->shouldReceive('offsetGet')->with('PreAuthRequest')->andReturn($preAuthRequest);
        $this->storage->shouldReceive('getMetadata')->with('PreAuthRequest');
        $this->storage->shouldReceive('getRequestAccessTime');
    }
}