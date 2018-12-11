<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Authentication\Result;
use Zend\Http\Response;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;
use DateTime;

class AuthControllerTest extends AbstractControllerTest
{
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
        parent::setUp();

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

        $this->setIdentity(null);
    }

    protected function getController(string $controllerName)
    {
        /** @var AuthController $controller */
        $controller = parent::getController($controllerName);

        $controller->setLpaApplicationService($this->lpaApplicationService);

        return $controller;
    }

    public function testIndexActionFormInvalid()
    {
        $controller = $this->getController(AuthController::class);

        $this->form->shouldReceive('isValid')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionFormAuthenticationFailed()
    {
        $controller = $this->getController(AuthController::class);

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
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('Authentication Failed', $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionFormAuthenticationSuccessfulDashboard()
    {
        $controller = $this->getController(AuthController::class);

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

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirect()
    {
        $controller = $this->getController(AuthController::class);

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
        $result = $controller->indexAction();
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirectLpa()
    {
        $controller = $this->getController(AuthController::class);

        $identity = new UserIdentity($this->user->id, 'ABC', 60 * 60, new DateTime('today midnight'));

        $authenticationResult = new Result(1, $identity);
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
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([$lpa->id, 'ABC'])->andReturn($lpa);

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $lpa->id], []])->andReturn($response)->once();

        /** @var ViewModel $result */
        Container::setDefaultManager($this->sessionManager);
        $result = $controller->indexAction();
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }

    public function testLogoutAction()
    {
        $controller = $this->getController(AuthController::class);

        $response = new Response();

        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage'=>true]])->once();
        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://www.gov.uk/done/lasting-power-of-attorney'])->andReturn($response)->once();

        $result = $controller->logoutAction();

        $this->assertEquals($response, $result);
    }

    public function testDeletedAction()
    {
        $controller = $this->getController(AuthController::class);

        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage'=>true]])->once();

        $result = $controller->deletedAction();

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
