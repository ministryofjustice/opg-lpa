<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CorrespondentController;
use Application\Form\Lpa\CorrespondentForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeSharedTest\DataModel\FixturesData;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

final class CorrespondentControllerTest extends AbstractControllerTestCase
{
    private MockInterface|CorrespondentForm $form;
    private array $postDataNoContact = [
        'contactInWelsh' => false,
        'correspondence' => [
            'contactByPost' => false,
            'contactByEmail' => false,
            'contactByPhone' => false,
        ]
    ];
    private array $postDataContact = [
        'contactInWelsh' => false,
        'correspondence' => [
            'contactByPost' => true,
            'contactByEmail' => true,
            'email-address' => 'unit@test.com',
            'contactByPhone' => true,
            'phone-number' => '0123456789'
        ]
    ];
    private array $postDataCorrespondence = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'email' => ['address' => 'unit@test.com'],
        'phone' => ['number' => '0123456789']
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(CorrespondentForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CorrespondentForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CorrespondenceForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($this->lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->document->correspondent->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals($this->lpa->document->correspondent->email, $result->getVariable('contactEmail'));
        $this->assertEquals($this->lpa->document->correspondent->phone->number, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testIndexActionGetCorrespondentTrustCorporation(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent = null;
        $trust = FixturesData::getAttorneyTrust(4);
        $this->lpa->document->primaryAttorneys[] = $trust;
        $this->lpa->document->whoIsRegistering = [4];
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $trust->email->address,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($trust->name, $result->getVariable('correspondentName'));
        $this->assertEquals($trust->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals($trust->email, $result->getVariable('contactEmail'));
        $this->assertEquals(null, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(true, $result->getVariable('allowEditButton'));
    }

    public function testIndexActionGetNoCorrespondentDonor(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($this->lpa->document->donor->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->document->donor->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals($this->lpa->document->donor->email, $result->getVariable('contactEmail'));
        $this->assertEquals(null, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testIndexActionGetNoCorrespondentAttorney(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = [1];
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => false,
                'email-address'  => null,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals(null, $result->getVariable('contactEmail'));
        $this->assertEquals(null, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testIndexActionGetCorrespondentCompany(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent->company = 'A Company Ltd.';
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Hon Ayden Armstrong, A Company Ltd.', $result->getVariable('correspondentName'));
        $this->assertEquals(false, $result->allowEditButton);
    }

    public function testIndexActionGetCorrespondentOther(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent->who = Correspondence::WHO_OTHER;
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ]])->once();
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Hon Ayden Armstrong', $result->getVariable('correspondentName'));
        $this->assertEquals(true, $result->allowEditButton);
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostInvalid($this->form);
        $this->setMatchedRouteName($controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($this->lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->document->correspondent->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals($this->lpa->document->correspondent->email, $result->getVariable('contactEmail'));
        $this->assertEquals($this->lpa->document->correspondent->phone->number, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testIndexActionPostFailure(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostValid($this->form, $this->postDataNoContact);
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoContact)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->contactInWelsh === false
                    && $correspondent->contactByPost === false;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set correspondent for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostValid($this->form, $this->postDataContact);
        $this->form->shouldReceive('getData')->andReturn($this->postDataContact)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->contactInWelsh === false
                    && $correspondent->contactByPost === true
                    && $correspondent->email->address === 'unit@test.com'
                    && $correspondent->phone->number === '0123456789';
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/correspondent');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/who-are-you', $location);
    }

    public function testEditActionGet(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->correspondent->flatten()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testEditActionPostInvalid(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostInvalid($this->form);
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testEditActionPostFailed(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent for id: 91333263035');

        $controller->editAction();
    }

    public function testEditActionPostSuccess(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessNoJs(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();

        $result = $controller->editAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $location);
    }

    public function testEditActionGetReuseDetailsNull(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(null)->once();
        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/correspondent');

        $result = $controller->editAction();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString(
            'lpa/91333263035/reuse-details?',
            $result->getHeaderLine('Location')
        );
    }

    public function testEditActionPostReuseDonorDetailsFormEditable(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(1)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');

        $routeMatch = $this->setReuseDetails($controller, $this->form, $this->user, 'donor');
        $this->form->shouldReceive('isEditable')->andReturn(true);
        $this->setMatchedRouteName($controller, 'lpa/correspondent/edit', $routeMatch);
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/correspondent/edit")->once();
        $routeMatch->shouldReceive('getParam')
            ->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/correspondent/edit")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/correspondent/edit", $result->backButtonUrl);
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testEditActionPostReuseDonorDetailsFormNotEditable(): void
    {
        /** @var CorrespondentController $controller */
        $controller = $this->getController(TestableCorrespondentController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(1)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');

        $this->setReuseDetails($controller, $this->form, $this->user, 'donor');
        $this->form->shouldReceive('isEditable')->andReturn(false);
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();
        $result = $controller->editAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $location);
    }
}
