<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CorrespondentController;
use Application\Form\Lpa\CorrespondentForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class CorrespondentControllerTest extends AbstractControllerTest
{
    /**
     * @var CorrespondentController
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

    public function setUp()
    {
        $this->controller = new CorrespondentController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(CorrespondentForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CorrespondentForm')->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CorrespondenceForm', ['lpa' => $this->lpa])->andReturn($this->form);
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
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

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
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $trust->email->address,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

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
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

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
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => false,
                'email-address'  => null,
                'contactByPhone' => false,
                'phone-number'   => null,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

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
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Hon Ayden Armstrong, A Company Ltd.', $result->getVariable('correspondentName'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->setPostInvalid($this->form, []);
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

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
        $postData = [
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByPost' => false,
                'contactByEmail' => false,
                'contactByPhone' => false,
            ]
        ];

        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')->withArgs(function ($lpaId, $correspondent) {
            return $lpaId === $this->lpa->id
                && $correspondent->contactInWelsh === false
                && $correspondent->contactByPost === false;
        })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();
        $postData = [
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByPost' => true,
                'contactByEmail' => true,
                'email-address' => 'unit@test.com',
                'contactByPhone' => true,
                'phone-number' => '0123456789'
            ]
        ];

        $this->lpa->document->correspondent = null;
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')->withArgs(function ($lpaId, $correspondent) {
            return $lpaId === $this->lpa->id
                && $correspondent->contactInWelsh === false
                && $correspondent->contactByPost === true
                && $correspondent->email->address === 'unit@test.com'
                && $correspondent->phone->number === '0123456789';
        })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/correspondent');
        $this->redirect->shouldReceive('toRoute')->with('lpa/who-are-you', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testEditActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/correspondent/edit")->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', "lpa/{$this->lpa->id}/correspondent/edit"])->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->correspondent->flatten()])->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

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
        $this->params->shouldReceive('fromQuery')->withArgs(['reuse-details'])->andReturn('existing-correspondent')->once();
        $this->setPostInvalid($this->form, []);
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/correspondent/edit', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/correspondent/edit")->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', "lpa/{$this->lpa->id}/correspondent/edit"])->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/correspondent', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/correspondent")->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/correspondent", $result->cancelUrl);
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }
}