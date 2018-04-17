<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\TrustCorporationForm;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Applicant;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class PrimaryAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var TestablePrimaryAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $primaryAttorneyForm;
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
    /**
     * @var MockInterface|Applicant
     */
    private $applicantService;

    public function setUpController(Lpa $lpa = null)
    {
        $this->controller = parent::controllerSetUp(TestablePrimaryAttorneyController::class, true, $lpa);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->primaryAttorneyForm = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm', ['lpa' => $this->lpa]])
            ->andReturn($this->primaryAttorneyForm);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm'])->andReturn($this->primaryAttorneyForm);

        $this->trustCorporationForm = Mockery::mock(TrustCorporationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TrustCorporationForm'])->andReturn($this->trustCorporationForm);

        $this->applicantService = Mockery::mock(Applicant::class);
        $this->controller->setApplicantService($this->applicantService);
    }

    public function testIndexActionNoPrimaryAttorneys()
    {
        $this->setUpController();

        $this->lpa->document->primaryAttorneys = [];

        $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/add');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionMultiplePrimaryAttorneys()
    {
        $this->setUpController();

        $this->assertGreaterThan(0, count($this->lpa->document->primaryAttorneys));

        $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/add');

        $expectedPrimaryAttorneysParams = [];
        foreach ($this->lpa->document->primaryAttorneys as $idx => $primaryAttorney) {
            $editUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/edit', ['idx' => $idx]);
            $confirmDeleteUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/confirm-delete', ['idx' => $idx]);

            $expectedPrimaryAttorneysParams[] = [
                'attorney' => [
                    'name' => $primaryAttorney->name,
                    'address' => $primaryAttorney->address
                ],
                'editUrl' => $editUrl,
                'confirmDeleteUrl' => $confirmDeleteUrl
            ];
        }

        $this->setMatchedRouteName($this->controller, 'lpa/primary-attorney');
        $nextUrl = $this->setUrlFromRoute($this->lpa, 'lpa/how-primary-attorneys-make-decision', null, $this->getExpectedRouteOptions('lpa/how-primary-attorneys-make-decision'));

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($nextUrl, $result->nextUrl);
        $this->assertEquals($expectedPrimaryAttorneysParams, $result->attorneys);
    }

    public function testAddActionGetReuseDetails()
    {
        $this->setUpController();

        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/primary-attorney/add', $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGet()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetExistingTrust()
    {
        $this->setUpController();

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
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
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostInvalid()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->primaryAttorneyForm, [], null, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to add a primary attorney for id: 91333263035
     */
    public function testAddActionPostFailed()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(false)->once();

        $this->controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/primary-attorney');
        $this->setRedirectToRoute('lpa/how-primary-attorneys-make-decision', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostUpdateWhoIsRegistering()
    {
        $this->setUpController();

        $response = new Response();

        $this->lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $this->lpa->document->whoIsRegistering = [];

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/primary-attorney');
        $this->setRedirectToRoute('lpa/how-primary-attorneys-make-decision', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostReuseDetails()
    {
        $this->setUpController();

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add', 2);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');
        $routeMatch = $this->setReuseDetails($this->controller, $this->primaryAttorneyForm, $this->user, 'attorney');
        $this->setMatchedRouteName($this->controller, 'lpa/primary-attorney/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionGetRedirectToAddHuman()
    {
        $this->setUpController(FixturesData::getHwLpa());

        $response = new Response();

        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setRedirectToRoute('lpa/primary-attorney/add', $this->lpa, $response);

        $result = $this->controller->addTrustAction();

        $this->assertEquals($response, $result);
    }

    public function testAddTrustActionGet()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostInvalid()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->trustCorporationForm);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to add a trust corporation attorney for id: 91333263035
     */
    public function testAddTrustActionPostFailed()
    {
        $this->setUpController();

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email']);
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
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/primary-attorney');
        $this->setRedirectToRoute('lpa/how-primary-attorneys-make-decision', $this->lpa, $response);

        $result = $this->controller->addTrustAction();

        $this->assertEquals($response, $result);
    }

    public function testAddTrustActionPostReuseDetails()
    {
        $this->setUpController();

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust', 2);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');
        $routeMatch = $this->setReuseDetails($this->controller, $this->trustCorporationForm, $this->user, 'attorney');
        $this->setMatchedRouteName($this->controller, 'lpa/primary-attorney/add-trust', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
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
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->primaryAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGetTrust()
    {
        $this->setUpController();

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->primaryAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
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
        $this->setPostInvalid($this->primaryAttorneyForm);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update a primary attorney 0 for id: 91333263035
     */
    public function testEditActionPostFailed()
    {
        $this->setUpController();

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
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
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostCorrespondent()
    {
        $this->setUpController();

        $attorney = $this->lpa->document->primaryAttorneys[0];
        $correspondent = new Correspondence();
        $correspondent->name = new LongName($attorney->name->flatten());
        $correspondent->address = $attorney->address;
        $correspondent->who = Correspondence::WHO_ATTORNEY;
        $this->lpa->document->correspondent = $correspondent;

        $idx = 0;

        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setExistingActorNamesData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
            })->andReturn(true)->once();

        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent) {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataHuman['name'])
                    && $correspondent->address == new Address($this->postDataHuman['address']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessTrust()
    {
        $this->setUpController();

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email'])
                    && $primaryAttorneyId === 4;
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
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
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
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(false, $result->isPopup);
    }

    public function testConfirmDeleteActionTrust()
    {
        $this->setUpController();

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
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
     * @expectedExceptionMessage API client failed to delete a primary attorney 0 for id: 91333263035
     */
    public function testDeleteActionFailed()
    {
        $this->setUpController();

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(false)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);

        $this->controller->deleteAction();
    }

    public function testDeleteActionSuccess()
    {
        $this->setUpController();

        $response = new Response();

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);

        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }

    public function testDeleteActionOneAttorneyRemaining()
    {
        $this->setUpController();

        $response = new Response();

        while (count($this->lpa->document->primaryAttorneys) > 2) {
            unset($this->lpa->document->primaryAttorneys[count($this->lpa->document->primaryAttorneys) - 1]);
        }

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->how === null
                    && $primaryAttorneyDecisions->howDetails === null;
            })->andReturn(true)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }

    public function testDeleteActionAttorneyRegistering()
    {
        $this->setUpController();

        $response = new Response();

        $this->lpa->document->whoIsRegistering = [1,2,3];

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }

    public function testDeleteActionAllAttorneyRegistering()
    {
        $this->setUpController();

        $response = new Response();

        $this->lpa->document->whoIsRegistering = [1];

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setRedirectToRoute('lpa/primary-attorney', $this->lpa, $response);

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }
}
