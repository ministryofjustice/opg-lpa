<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CorrespondentController;
use Application\Form\Lpa\CorrespondentForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Uri\Uri;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class CorrespondentControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableCorrespondentController
     */
    private $controller;
    /**
     * @var MockInterface|CorrespondentForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postDataNoContact = [
        'contactInWelsh' => false,
        'correspondence' => [
            'contactByPost' => false,
            'contactByEmail' => false,
            'contactByPhone' => false,
        ]
    ];
    private $postDataContact = [
        'contactInWelsh' => false,
        'correspondence' => [
            'contactByPost' => true,
            'contactByEmail' => true,
            'email-address' => 'unit@test.com',
            'contactByPhone' => true,
            'phone-number' => '0123456789'
        ]
    ];
    private $postDataCorrespondence = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'email' => ['address' => 'unit@test.com'],
        'phone' => ['number' => '0123456789']
    ];

    public function setUp()
    {
        $this->controller = new TestableCorrespondentController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(CorrespondentForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CorrespondentForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CorrespondenceForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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

    public function testIndexActionGetCorrespondentTrustCorporation()
    {
        $this->lpa->document->correspondent = null;
        $trust = FixturesData::getAttorneyTrust(4);
        $this->lpa->document->primaryAttorneys[] = $trust;
        $this->lpa->document->whoIsRegistering = [4];
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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

    public function testIndexActionGetNoCorrespondentDonor()
    {
        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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

    public function testIndexActionGetNoCorrespondentAttorney()
    {
        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = [1];
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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

    public function testIndexActionGetCorrespondentCompany()
    {
        $this->lpa->document->correspondent->company = 'A Company Ltd.';
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Hon Ayden Armstrong, A Company Ltd.', $result->getVariable('correspondentName'));
        $this->assertEquals(false, $result->allowEditButton);
    }

    public function testIndexActionGetCorrespondentOther()
    {
        $this->lpa->document->correspondent->who = Correspondence::WHO_OTHER;
        $this->controller->setLpa($this->lpa);
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
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Hon Ayden Armstrong', $result->getVariable('correspondentName'));
        $this->assertEquals(true, $result->allowEditButton);
    }

    public function testIndexActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostInvalid($this->form);
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set correspondent for id: 91333263035
     */
    public function testIndexActionPostFailure()
    {
        $this->controller->setLpa($this->lpa);
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostValid($this->form, $this->postDataNoContact);
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoContact)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->contactInWelsh === false
                    && $correspondent->contactByPost === false;
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->controller->setLpa($this->lpa);
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent');
        $this->setPostValid($this->form, $this->postDataContact);
        $this->form->shouldReceive('getData')->andReturn($this->postDataContact)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->contactInWelsh === false
                    && $correspondent->contactByPost === true
                    && $correspondent->email->address === 'unit@test.com'
                    && $correspondent->phone->number === '0123456789';
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/correspondent');
        $this->setRedirectToRoute('lpa/who-are-you', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testEditActionGet()
    {
        $this->controller->setLpa($this->lpa);
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
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testEditActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostInvalid($this->form);
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update correspondent for id: 91333263035
     */
    public function testEditActionPostFailed()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(false)->once();

        $this->controller->editAction();
    }

    public function testEditActionPostSuccess()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionPostSuccessNoJs()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostValid($this->form, $this->postDataCorrespondence);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();
        $this->setRedirectToRoute('lpa/correspondent', $this->lpa, $response);

        $result = $this->controller->editAction();

        $this->assertEquals($response, $result);
    }

    public function testEditActionGetReuseDetailsNull()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(null)->once();
        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/correspondent', $response);

        $result = $this->controller->editAction();

        $this->assertEquals($response, $result);
    }

    public function testEditActionPostReuseDonorDetailsFormEditable()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(1)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');

        $routeMatch = $this->setReuseDetails($this->controller, $this->form, $this->user, 'donor');
        $this->form->shouldReceive('isEditable')->andReturn(true);
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent/edit', $routeMatch);
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
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/correspondent/edit", $result->backButtonUrl);
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }

    public function testEditActionPostReuseDonorDetailsFormNotEditable()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn(1)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/correspondent/edit');

        $this->setReuseDetails($this->controller, $this->form, $this->user, 'donor');
        $this->form->shouldReceive('isEditable')->andReturn(false);
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postDataCorrespondence)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id
                    && $correspondent->name == new LongName($this->postDataCorrespondence['name'])
                    && $correspondent->email == new EmailAddress($this->postDataCorrespondence['email'])
                    && $correspondent->phone == new PhoneNumber($this->postDataCorrespondence['phone']);
            })->andReturn(true)->once();
        $this->setRedirectToRoute('lpa/correspondent', $this->lpa, $response);

        $result = $this->controller->editAction();

        $this->assertEquals($response, $result);
    }
}
