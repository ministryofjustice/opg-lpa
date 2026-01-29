<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PrimaryAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\TrustCorporationForm;
use Application\Model\Service\Lpa\Applicant;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeSharedTest\DataModel\FixturesData;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

final class PrimaryAttorneyControllerTest extends AbstractControllerTestCase
{
    private MockInterface|AttorneyForm $primaryAttorneyForm;
    private MockInterface|TrustCorporationForm $trustCorporationForm;
    private array $postDataHuman = [
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
    private array $postDataTrust = [
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

    public function setUp(): void
    {
        parent::setUp();

        $this->primaryAttorneyForm = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm', ['lpa' => $this->lpa]])
            ->andReturn($this->primaryAttorneyForm);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\AttorneyForm'])->andReturn($this->primaryAttorneyForm);

        $this->trustCorporationForm = Mockery::mock(TrustCorporationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TrustCorporationForm'])->andReturn($this->trustCorporationForm);
    }

    protected function getController(string $controllerName)
    {
        /** @var PrimaryAttorneyController $controller */
        $controller = parent::getController($controllerName);

        $this->applicantService = Mockery::mock(Applicant::class);
        $controller->setApplicantService($this->applicantService);

        return $controller;
    }

    public function testIndexActionNoPrimaryAttorneys(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneys = [];

        $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/add');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionMultiplePrimaryAttorneys(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

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

        $this->setMatchedRouteName($controller, 'lpa/primary-attorney');
        $nextUrl = $this->setUrlFromRoute($this->lpa, 'lpa/how-primary-attorneys-make-decision', null, $this->getExpectedRouteOptions('lpa/how-primary-attorneys-make-decision'));

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($nextUrl, $result->nextUrl);
        $this->assertEquals($expectedPrimaryAttorneysParams, $result->attorneys);
    }

    public function testAddActionGetReuseDetails(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/primary-attorney/add');

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString(
            'lpa/91333263035/reuse-details?',
            $result->getHeaders()->get('Location')->getUri()
        );
    }

    public function testAddActionGet(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetExistingTrust(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetNoTrustHw(): void
    {
        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostInvalid(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->primaryAttorneyForm, [], null, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostFailed(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a primary attorney for id: 91333263035');

        $controller->addAction();
    }

    public function testAddActionPostSuccess(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/primary-attorney');

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/how-primary-attorneys-make-decision', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionPostUpdateWhoIsRegistering(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $this->lpa->document->whoIsRegistering = [];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add');
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/primary-attorney');

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/how-primary-attorneys-make-decision', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionPostReuseDetails(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/add', 2);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');
        $routeMatch = $this->setReuseDetails($controller, $this->primaryAttorneyForm, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/primary-attorney/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionGetRedirectToAddHuman(): void
    {
        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $result = $controller->addTrustAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/5531003156/primary-attorney/add', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddTrustActionGet(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostInvalid(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->trustCorporationForm);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostFailed(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a trust corporation attorney for id: 91333263035');

        $controller->addTrustAction();
    }

    public function testAddTrustActionPostSuccess(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust, null, 1, 2);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/primary-attorney');
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/how-primary-attorneys-make-decision', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddTrustActionPostReuseDetails(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/add-trust', 2);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');
        $routeMatch = $this->setReuseDetails($controller, $this->trustCorporationForm, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/primary-attorney/add-trust', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/primary-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/primary-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testEditActionInvalidIndex(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testEditActionGet(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->primaryAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGetTrust(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->primaryAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostInvalid(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostInvalid($this->primaryAttorneyForm);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/primary-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->primaryAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostFailed(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update a primary attorney 0 for id: 91333263035');

        $controller->editAction();
    }

    public function testEditActionPostSuccess(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostCorrespondent(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $attorney = $this->lpa->document->primaryAttorneys[0];
        $correspondent = new Correspondence();
        $correspondent->name = new LongName($attorney->name->flatten());
        $correspondent->address = $attorney->address;
        $correspondent->who = Correspondence::WHO_ATTORNEY;
        $this->lpa->document->correspondent = $correspondent;

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->primaryAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->primaryAttorneyForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->primaryAttorneyForm->shouldReceive('setActorData')->once();
        $this->primaryAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name == new Name($this->postDataHuman['name'])
                    && $primaryAttorney->address == new Address($this->postDataHuman['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $primaryAttorneyId === 1;
            })->andReturn(true)->once();

        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataHuman['name'])
                    && $correspondent->address == new Address($this->postDataHuman['address']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessTrust(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/primary-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorney')
            ->withArgs(function ($lpa, $primaryAttorney, $primaryAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorney->name === $this->postDataTrust['name']
                    && $primaryAttorney->number === $this->postDataTrust['number']
                    && $primaryAttorney->address == new Address($this->postDataTrust['address'])
                    && $primaryAttorney->email == new EmailAddress($this->postDataTrust['email'])
                    && $primaryAttorneyId === 4;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testConfirmDeleteActionInvalidIndex(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testConfirmDeleteActionGetJs(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testConfirmDeleteActionGetNoJs(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(false, $result->isPopup);
    }

    public function testConfirmDeleteActionTrust(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $idx = count($this->lpa->document->primaryAttorneys) - 1;
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/primary-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->primaryAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(true, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testDeleteActionInvalidIndex(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->deleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testDeleteActionFailed(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(false)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete a primary attorney 0 for id: 91333263035');

        $controller->deleteAction();
    }

    public function testDeleteActionSuccess(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);

        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $result->getHeaders()->get('Location')->getUri());
    }

    public function testDeleteActionOneAttorneyRemaining(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        while (count($this->lpa->document->primaryAttorneys) > 2) {
            unset($this->lpa->document->primaryAttorneys[count($this->lpa->document->primaryAttorneys) - 1]);
        }

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->how === null
                    && $primaryAttorneyDecisions->howDetails === null;
            })->andReturn(true)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);

        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $result->getHeaders()->get('Location')->getUri());
    }

    public function testDeleteActionAttorneyRegistering(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->whoIsRegistering = [1,2,3];

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $result->getHeaders()->get('Location')->getUri());
    }

    public function testDeleteActionAllAttorneyRegistering(): void
    {
        $controller = $this->getController(TestablePrimaryAttorneyController::class);

        $this->lpa->document->whoIsRegistering = [1];

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();

        $this->lpaApplicationService->shouldReceive('deletePrimaryAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true)->once();
        $this->applicantService->shouldReceive('removeAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->primaryAttorneys[$idx]->id])->andReturn(true);
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);

        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $result->getHeaders()->get('Location')->getUri());
    }
}
