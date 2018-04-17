<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\TrustCorporationForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ReplacementAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableReplacementAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $replacementAttorneyForm;
    /**
     * @var MockInterface|TrustCorporationForm
     */
    private $trustCorporationForm;
    private $postDataHuman = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'address' => [
            'address1' => 'Address line 1',
            'address2' => 'Address line 2',
            'address3' => 'Address line 3',
            'postcode' => 'PO5 3DE'
        ],
        'email' => ['address' => 'unit@test.com']
    ];
    private $postDataTrust = [
        'name' => 'Unit Test Company',
        'number' => '0123456789',
        'address' => [
            'address1' => 'Address line 1',
            'address2' => 'Address line 2',
            'address3' => 'Address line 3',
            'postcode' => 'PO5 3DE'
        ],
        'email' => ['address' => 'unit@test.com']
    ];

    public function setUpController(Lpa $lpa = null)
    {
        $this->controller = parent::controllerSetUp(TestableReplacementAttorneyController::class, true, $lpa);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->blankMainFlowForm = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm);

        $this->replacementAttorneyForm = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm', ['lpa' => $this->lpa]])
            ->andReturn($this->replacementAttorneyForm);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm'])->andReturn($this->replacementAttorneyForm);

        $this->trustCorporationForm = Mockery::mock(TrustCorporationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TrustCorporationForm'])->andReturn($this->trustCorporationForm);
    }

    public function testIndexActionGetNoReplacementAttorney()
    {
        $this->setUpController();

        $this->lpa->document->replacementAttorneys = [];

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney');
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals([], $result->getVariable('attorneys'));
    }

    public function testIndexActionGetMultipleReplacementAttorney()
    {
        $this->setUpController();

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney');
        $expectedAttorneyParams = $this->getExpectedAttorneyParams();
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($expectedAttorneyParams, $result->getVariable('attorneys'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->setUpController();

        $this->setPostInvalid($this->blankMainFlowForm);
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney');
        $expectedAttorneyParams = $this->getExpectedAttorneyParams();
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($expectedAttorneyParams, $result->getVariable('attorneys'));
    }

    public function testIndexActionPostSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $this->setPostValid($this->blankMainFlowForm);
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/replacement-attorney');
        $this->setRedirectToRoute('lpa/when-replacement-attorney-step-in', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetReuseDetails()
    {
        $this->setUpController();

        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/replacement-attorney/add', $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGet()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetExistingTrust()
    {
        $this->setUpController();

        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetNoTrustHw()
    {
        $this->setUpController(FixturesData::getHwLpa());

        $this->lpa->seed = null;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostInvalid()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->replacementAttorneyForm, [], null, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to add a replacement attorney for id: 91333263035
     */
    public function testAddActionPostFailed()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(false)->once();

        $this->controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/replacement-attorney');
        $this->setRedirectToRoute('lpa/when-replacement-attorney-step-in', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostMetadata()
    {
        $this->setUpController();

        $response = new Response();

        unset($this->lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/replacement-attorney');
        $this->setRedirectToRoute('lpa/when-replacement-attorney-step-in', $this->lpa, $response);
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostReuseDetails()
    {
        $this->setUpController();

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add', 2);
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');
        $routeMatch = $this->setReuseDetails($this->controller, $this->replacementAttorneyForm, $this->user, 'attorney');
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionGetRedirectToAddHuman()
    {
        $this->setUpController(FixturesData::getHwLpa());

        $response = new Response();

        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setRedirectToRoute('lpa/replacement-attorney/add', $this->lpa, $response);

        $result = $this->controller->addTrustAction();

        $this->assertEquals($response, $result);
    }

    public function testAddTrustActionGet()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostInvalid()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->trustCorporationForm);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to add trust corporation replacement attorney for id: 91333263035
     */
    public function testAddTrustActionPostFailed()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(false)->once();

        $this->controller->addTrustAction();
    }

    public function testAddTrustActionPostSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust, null, 1, 2);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/replacement-attorney');
        $this->setRedirectToRoute('lpa/when-replacement-attorney-step-in', $this->lpa, $response);

        $result = $this->controller->addTrustAction();

        $this->assertEquals($response, $result);
    }

    public function testAddTrustActionPostMetadata()
    {
        $this->setUpController();

        $response = new Response();

        unset($this->lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust, null, 1, 2);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/replacement-attorney');
        $this->setRedirectToRoute('lpa/when-replacement-attorney-step-in', $this->lpa, $response);
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();

        $result = $this->controller->addTrustAction();

        $this->assertEquals($response, $result);
    }

    public function testAddTrustActionPostReuseDetails()
    {
        $this->setUpController();

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust', 2);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');
        $routeMatch = $this->setReuseDetails($this->controller, $this->trustCorporationForm, $this->user, 'attorney');
        $this->setMatchedRouteName($this->controller, 'lpa/replacement-attorney/add-trust', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testEditActionInvalidIndex()
    {
        $this->setUpController();

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($this->controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $this->controller->setEvent($event);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testEditActionGet()
    {
        $this->setUpController();

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->replacementAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGetTrust()
    {
        $this->setUpController();

        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->replacementAttorneys) - 1;
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->replacementAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostInvalid()
    {
        $this->setUpController();

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostInvalid($this->replacementAttorneyForm);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update replacement attorney 1 for id: 91333263035
     */
    public function testEditActionPostFailed()
    {
        $this->setUpController();

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $replacementAttorneyId === 1;
            })->andReturn(false)->once();

        $this->controller->editAction();
    }

    public function testEditActionPostSuccess()
    {
        $this->setUpController();

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $replacementAttorneyId === 1;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessTrust()
    {
        $this->setUpController();

        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->replacementAttorneys) - 1;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email'])
                    && $replacementAttorneyId === 4;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testConfirmDeleteActionInvalidIndex()
    {
        $this->setUpController();

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($this->controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $this->controller->setEvent($event);

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testConfirmDeleteActionGetJs()
    {
        $this->setUpController();

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testConfirmDeleteActionGetNoJs()
    {
        $this->setUpController();

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(false, $result->isPopup);
    }

    public function testConfirmDeleteActionTrust()
    {
        $this->setUpController();

        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->replacementAttorneys) - 1;
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(true, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testDeleteActionInvalidIndex()
    {
        $this->setUpController();

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($this->controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $this->controller->setEvent($event);

        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $this->controller->deleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to delete replacement attorney 0 for id: 91333263035
     */
    public function testDeleteActionFailed()
    {
        $this->setUpController();

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteReplacementAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->replacementAttorneys[$idx]->id])
            ->andReturn(false)->once();

        $this->controller->deleteAction();
    }

    public function testDeleteActionSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteReplacementAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->replacementAttorneys[$idx]->id])->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setRedirectToRoute('lpa/replacement-attorney', $this->lpa, $response);

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @return array
     */
    private function getExpectedAttorneyParams()
    {
        $expectedAttorneyParams = [];
        foreach ($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $editUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/edit', ['idx' => $idx]);
            $confirmDeleteUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/confirm-delete', ['idx' => $idx]);
            $deleteUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);

            $expectedAttorneyParams[] = [
                'attorney' => [
                    'address' => $attorney->address,
                    'name' => $attorney->name
                ],
                'editRoute' => $editUrl,
                'confirmDeleteRoute' => $confirmDeleteUrl,
                'deleteRoute' => $deleteUrl,
            ];
        }
        return $expectedAttorneyParams;
    }
}
