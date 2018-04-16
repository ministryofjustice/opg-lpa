<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Authentication\Result;
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
     * @var MockInterface|Login
     */
    private $form;
    private $postData = [
        'email' => 'unit@test.com',
        'password' => 'unitTest'
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(AuthController::class, false);
        $this->controller->setLpaApplicationService($this->lpaApplicationService);

        $this->request->shouldReceive('getMethod')->andReturn('POST');
        $this->request->shouldReceive('isPost')->andReturn(true);
        $this->request->shouldReceive('getPost')->andReturn($this->postData);

        $this->form = Mockery::mock(Login::class);
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'login']);
        $this->form->shouldReceive('setData')->withArgs([$this->postData]);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Login'])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')->withArgs(['login'])->andReturn('login');

        $this->sessionManager->shouldReceive('initialise');
    }

    public function testIndexActionFormInvalid()
    {
        $this->form->shouldReceive('isValid')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionFormAuthenticationFailed()
    {
        $authenticationResult = new Result(0, null, ['Authentication Failed']);

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationService->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('authenticate')
            ->andReturn($authenticationResult)
            ->once();

        $this->form->shouldReceive('setData')->withArgs([['email' => $this->postData['email']]])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
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

        $this->authenticationService->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('authenticate')
            ->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->withArgs([true])->once();

        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

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

        $this->authenticationService->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('authenticate')
            ->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->withArgs([true])->once();

        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://localhost/user/about-you'])->andReturn($response)->once();

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

        $this->setPreAuthRequestUrl('https://localhost/lpa/3503563157/when-lpa-starts');

        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->twice();

        $this->authenticationService->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService->shouldReceive('authenticate')
            ->andReturn($authenticationResult)->once();

        $this->sessionManager->shouldReceive('regenerateId')->withArgs([true])->once();

        $lpa = new Lpa();
        $lpa->id = 3503563157;
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([$lpa->id])->andReturn($lpa);

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $lpa->id], []])->andReturn($response)->once();

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
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage'=>true]])->once();
        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://www.gov.uk/done/lasting-power-of-attorney'])->andReturn($response)->once();

        $result = $this->controller->logoutAction();

        $this->assertEquals($response, $result);
    }

    public function testDeletedAction()
    {
        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage'=>true]])->once();

        $result = $this->controller->deletedAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    private function setPreAuthRequestUrl($url)
    {
        $this->sessionManager->shouldReceive('start')->once();
        $this->storage->offsetSet('PreAuthRequest', new ArrayObject(['url' => $url]));
    }
}
