<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\RegisterController;
use Application\Form\User\ConfirmEmail;
use Application\Form\User\Registration;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Header\Referer;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Uri\Uri;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\MockInterface;

final class RegisterControllerTest extends AbstractControllerTestCase
{
    public const GA = 987654321987654321;
    private MockInterface|Registration $form;
    /**
     * @var MockInterface|MvcEvent
     */
    private $event;

    private array $postData = [
        'email' => 'unit@test.com',
        'password' => 'password'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(Registration::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Registration'])->andReturn($this->form);

        $this->sessionManagerSupport = \Mockery::mock(
            SessionManagerSupport::class,
            [$this->sessionManager]
        )->makePartial();

        $this->sessionManagerSupport
            ->shouldReceive('getSessionManager')
            ->andReturn($this->sessionManager)
            ->byDefault();
    }

    private function makeMockReferer(string $url)
    {
        $uri = new Uri($url);
        $referer = Mockery::mock(Referer::class);
        $referer->shouldReceive('uri')->once()->andReturn($uri);
        return $referer;
    }

    protected function getController(string $controllerName)
    {
        /** @var RegisterController $controller */
        $controller = parent::getController($controllerName);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->event = Mockery::mock(MvcEvent::class);
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $controller->setEvent($this->event);

        $this->userDetails = Mockery::mock(Details::class);
        $controller->setUserService($this->userDetails);

        return $controller;
    }

    public function testIndexActionRefererGovUk(): void
    {
        $controller = $this->getController(RegisterController::class);

        $response = new Response();

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('http://www.gov.uk');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['home', ['action' => 'index'], ['query' => ['_ga' => self::GA]]])
            ->andReturn($response)
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionAlreadyLoggedIn(): void
    {
        $controller = $this->getController(RegisterController::class);

        $response = new Response();

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['user/dashboard'])
            ->andReturn($response)
            ->once();

        $this->logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertSame(
                    'Authenticated user attempted to access registration page',
                    $message
                );

                $this->assertArrayHasKey('identity', $context);
                $this->assertSame($this->userIdentity->toArray(), $context['identity']);

                return true;
            });

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }


    public function testIndexActionGet(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    // this mirrors the case where the referer is not a valid URI, e.g.
    // if it is set to android-app://com.google.android.gm/ when clicking
    // a link in GMail; see LPAL-1151
    public function testIndexActionBadReferer(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        // this is how Laminas constructs headers from the incoming request;
        // in cases where the header cannot be generated correctly (e.g. a Referer header
        // with non-valid scheme), we end up with a GenericHeader instance instead
        $referer = Headers::fromString('Referer: android-app://com.google.android.gm/')->get('Referer');

        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostInvalid(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostError(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('registerAccount')->andReturn('Unit test error')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
        $this->assertEquals('Unit test error', $result->getVariable('error'));
    }

    public function testIndexActionPostSuccess(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();

        $referer = $this->makeMockReferer('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();

        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('registerAccount')->andReturn(true);

        //  Set up the confirm email form
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['register/resend-email'])
            ->andReturn('register/resend-email')
            ->once();
        $form = Mockery::mock(ConfirmEmail::class);
        $form->shouldReceive('setAttribute')->withArgs(['action', 'register/resend-email'])->once();
        $form->shouldReceive('populateValues')->withArgs([[
            'email' => $this->postData['email'],
            'email_confirm' => $this->postData['email'],
        ]])->once();

        $this->formElementManager->shouldReceive('get')
             ->withArgs(['Application\Form\User\ConfirmEmail'])->andReturn($form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionNoToken(): void
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('invalid-token', $result->getVariable('error'));
    }

    public function testConfirmActionAccountDoesNotExist(): void
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->sessionManagerSupport->shouldReceive('initialise')->once();
        $this->userDetails->shouldReceive('activateAccount')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('account-missing', $result->getVariable('error'));
    }

    public function testConfirmActionSuccess(): void
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->sessionManagerSupport->shouldReceive('initialise')->once();
        $this->userDetails->shouldReceive('activateAccount')->andReturn(true)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
}
