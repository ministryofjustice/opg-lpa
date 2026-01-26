<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReplacementAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\TrustCorporationForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

final class ReplacementAttorneyControllerTest extends AbstractControllerTestCase
{
    private MockInterface|BlankMainFlowForm $blankMainFlowForm;
    private MockInterface|AttorneyForm $replacementAttorneyForm;
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

    public function setUp(): void
    {
        parent::setUp();

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

    public function testIndexActionGetNoReplacementAttorney(): void
    {
        $this->lpa->document->replacementAttorneys = [];

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($controller, 'lpa/replacement-attorney');
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals([], $result->getVariable('attorneys'));
    }

    public function testIndexActionGetMultipleReplacementAttorney(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($controller, 'lpa/replacement-attorney');
        $expectedAttorneyParams = $this->getExpectedAttorneyParams();
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($expectedAttorneyParams, $result->getVariable('attorneys'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->setPostInvalid($this->blankMainFlowForm);
        $this->setMatchedRouteName($controller, 'lpa/replacement-attorney');
        $expectedAttorneyParams = $this->getExpectedAttorneyParams();
        $addRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/add');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($addRoute, $result->getVariable('addRoute'));
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
        $this->assertEquals($expectedAttorneyParams, $result->getVariable('attorneys'));
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->setPostValid($this->blankMainFlowForm);
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/replacement-attorney');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/when-replacement-attorney-step-in', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionGetReuseDetails(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/replacement-attorney/add');

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
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetExistingTrust(): void
    {
        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionGetNoTrustHw(): void
    {
        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(null, $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostInvalid(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->replacementAttorneyForm, [], null, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddActionPostFailed(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a replacement attorney for id: 91333263035');

        $controller->addAction();
    }

    public function testAddActionPostSuccess(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/replacement-attorney');

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/when-replacement-attorney-step-in', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionPostMetadata(): void
    {
        unset($this->lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman, null, 2, 2);
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add');
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/replacement-attorney');
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/when-replacement-attorney-step-in', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionPostReuseDetails(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/add', 2);
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');
        $routeMatch = $this->setReuseDetails($controller, $this->replacementAttorneyForm, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/replacement-attorney/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add-trust', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionGetRedirectToAddHuman(): void
    {
        $this->lpa = FixturesData::getHwLpa();
        $this->lpa->seed = null;

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/5531003156/replacement-attorney/add', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddTrustActionGet(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostInvalid(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->trustCorporationForm);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testAddTrustActionPostFailed(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add trust corporation replacement attorney for id: 91333263035');

        $controller->addTrustAction();
    }

    public function testAddTrustActionPostSuccess(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust, null, 1, 2);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/replacement-attorney');
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/when-replacement-attorney-step-in', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddTrustActionPostMetadata(): void
    {
        unset($this->lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust, null, 1, 2);
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust');
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('addReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email']);
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/replacement-attorney');
        $this->metadata->shouldReceive('setReplacementAttorneysConfirmed')->withArgs([$this->lpa])->once();

        $result = $controller->addTrustAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/when-replacement-attorney-step-in', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddTrustActionPostReuseDetails(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->setFormAction($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/add-trust', 2);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');
        $routeMatch = $this->setReuseDetails($controller, $this->trustCorporationForm, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/replacement-attorney/add-trust', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addTrustAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/replacement-attorney/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals('lpa/replacement-attorney/add', $result->switchAttorneyTypeRoute);
    }

    public function testEditActionInvalidIndex(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

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
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->replacementAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGetTrust(): void
    {
        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = count($this->lpa->document->replacementAttorneys) - 1;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('bind')
            ->withArgs([$this->getFlattenedAttorneyData($this->lpa->document->replacementAttorneys[$idx])])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/trust-form.twig', $result->getTemplate());
        $this->assertEquals($this->trustCorporationForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostInvalid(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostInvalid($this->replacementAttorneyForm);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/replacement-attorney/person-form.twig', $result->getTemplate());
        $this->assertEquals($this->replacementAttorneyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostFailed(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $replacementAttorneyId === 1;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update replacement attorney 1 for id: 91333263035');

        $controller->editAction();
    }

    public function testEditActionPostSuccess(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->replacementAttorneyForm, $this->postDataHuman);
        $this->setFormActionIndex($this->replacementAttorneyForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->replacementAttorneyForm->shouldReceive('setActorData')->once();
        $this->replacementAttorneyForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataHuman)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name == new Name($this->postDataHuman['name'])
                    && $replacementAttorney->address == new Address($this->postDataHuman['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataHuman['email'])
                    && $replacementAttorneyId === 1;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessTrust(): void
    {
        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = count($this->lpa->document->replacementAttorneys) - 1;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->trustCorporationForm, $this->postDataTrust);
        $this->setFormActionIndex($this->trustCorporationForm, $this->lpa, 'lpa/replacement-attorney/edit', $idx);
        $this->trustCorporationForm->shouldReceive('getModelDataFromValidatedForm')
            ->andReturn($this->postDataTrust)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorney')
            ->withArgs(function ($lpa, $replacementAttorney, $replacementAttorneyId): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorney->name === $this->postDataTrust['name']
                    && $replacementAttorney->number === $this->postDataTrust['number']
                    && $replacementAttorney->address == new Address($this->postDataTrust['address'])
                    && $replacementAttorney->email == new EmailAddress($this->postDataTrust['email'])
                    && $replacementAttorneyId === 4;
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testConfirmDeleteActionInvalidIndex(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

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
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testConfirmDeleteActionGetNoJs(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(false, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(false, $result->isPopup);
    }

    public function testConfirmDeleteActionTrust(): void
    {
        $this->lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();

        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = count($this->lpa->document->replacementAttorneys) - 1;
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/replacement-attorney');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->name, $result->attorneyName);
        $this->assertEquals($this->lpa->document->replacementAttorneys[$idx]->address, $result->attorneyAddress);
        $this->assertEquals(true, $result->isTrust);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testDeleteActionInvalidIndex(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

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
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteReplacementAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->replacementAttorneys[$idx]->id])
            ->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete replacement attorney 0 for id: 91333263035');

        $controller->deleteAction();
    }

    public function testDeleteActionSuccess(): void
    {
        /** @var ReplacementAttorneyController $controller */
        $controller = $this->getController(TestableReplacementAttorneyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteReplacementAttorney')
            ->withArgs([$this->lpa, $this->lpa->document->replacementAttorneys[$idx]->id])->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);

        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/replacement-attorney', $result->getHeaders()->get('Location')->getUri());
    }

    private function getExpectedAttorneyParams(): array
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
