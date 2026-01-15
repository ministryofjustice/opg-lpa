<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Session\ContainerNamespace;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Authentication\Result;
use Laminas\Http\Response;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use DateTime;
use Psr\Http\Message\ResponseInterface;

final class AuthControllerTest extends AbstractControllerTestCase
{
    private MockInterface|Login $form;
    private array $postData = [
        'email' => 'unit@test.com',
        'password' => 'unitTest'
    ];

    public function setUp(): void
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

        $this->setIdentity(null);
    }

    protected function getController(string $controllerName)
    {
        /** @var AuthController $controller */
        $controller = parent::getController($controllerName);

        $controller->setLpaApplicationService($this->lpaApplicationService);

        return $controller;
    }

    public function testIndexActionFormInvalid(): void
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

    public function testIndexActionFormAuthenticationFailed(): void
    {
        $controller = $this->getController(AuthController::class);

        $authenticationResult = new Result(0, null, ['Authentication Failed']);

        $this->form
            ->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

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

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(true)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->andReturn(null)
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

    public function testIndexActionFormAuthenticationSuccessfulDashboard(): void
    {
        $controller = $this->getController(AuthController::class);

        $authenticationResult = new Result(1, null);
        $response = new Response();

        $this->form
            ->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

        $this->authenticationService
            ->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService
            ->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService
            ->shouldReceive('authenticate')
            ->andReturn($authenticationResult)
            ->once();

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(true)
            ->once();
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->andReturn(null)
            ->once();

        $this->sessionManager
            ->shouldReceive('regenerateId')
            ->withArgs([true])
            ->once();

        $this->redirect
            ->shouldReceive('toRoute')
            ->withArgs(['user/dashboard'])
            ->andReturn($response)
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirect(): void
    {
        $controller = $this->getController(AuthController::class);

        $authenticationResult = new Result(1, null);

        $this->setPreAuthRequestUrl('https://localhost/user/about-you');

        $this->form
            ->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

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

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(true)
            ->once();

        $this->sessionManager
            ->shouldReceive('regenerateId')
            ->withArgs([true])
            ->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals(
            'https://localhost/user/about-you',
            $result->getHeaderLine('Location')
        );
    }

    public function testIndexActionFormAuthenticationSuccessfulRedirectLpa(): void
    {
        $controller = $this->getController(AuthController::class);

        $identity = new UserIdentity($this->user->id, 'ABC', 60 * 60, new DateTime('today midnight'));

        $authenticationResult = new Result(1, $identity);
        $response = new Response();

        $this->setPreAuthRequestUrl('https://localhost/lpa/3503563157/when-lpa-starts');

        $this->form
            ->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

        $this->authenticationService
            ->shouldReceive('setEmail')
            ->withArgs([$this->postData['email']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService
            ->shouldReceive('setPassword')
            ->withArgs([$this->postData['password']])
            ->andReturn($this->authenticationService)
            ->once();
        $this->authenticationService
            ->shouldReceive('authenticate')
            ->andReturn($authenticationResult)
            ->once();

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(true)
            ->once();

        $this->sessionManager
            ->shouldReceive('regenerateId')
            ->withArgs([true])
            ->once();

        $lpa = new Lpa();
        $lpa->id = 3503563157;
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([$lpa->id, 'ABC'])->andReturn($lpa);

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $lpa->id], []])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testSessionExpiryAction(): void
    {
        $controller = $this->getController(AuthController::class);

        $this->authenticationService->shouldReceive('getSessionExpiry')->once()->andReturn(100);

        /** @var JsonModel $result */
        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals('{"remainingSeconds":100}', $result->serialize());
    }

    public function testSessionExpiryActionExpired(): void
    {
        $controller = $this->getController(AuthController::class);

        $this->authenticationService->shouldReceive('getSessionExpiry')->once()->andReturn(null);
        $this->authenticationService->shouldReceive('clearIdentity')->once()->andReturn(null);
        $this->sessionManager->shouldReceive('destroy')
            ->with(['clear_storage' => true])
            ->once()
            ->andReturn(null);

        /** @var Response $result */
        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(204, $result->getStatusCode());
        $this->assertEquals([], $result->getHeaders()->toArray());
        $this->assertEquals('', $result->getBody());
    }

    public function testLogoutAction(): void
    {
        $controller = $this->getController(AuthController::class);

        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage' => true]])->once();

        $result = $controller->logoutAction();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals(
            'https://www.gov.uk/done/lasting-power-of-attorney',
            $result->getHeaderLine('Location')
        );
    }

    public function testDeletedAction(): void
    {
        $controller = $this->getController(AuthController::class);

        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([['clear_storage' => true]])->once();

        $result = $controller->deletedAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    private function setPreAuthRequestUrl(string $url): void
    {
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->andReturn($url)
            ->once();
    }
}
