<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ApplicantController;
use Application\Form\Lpa\ApplicantForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class ApplicantControllerTest extends AbstractControllerTest
{
    /**
     * @var ApplicantController
     */
    private $controller;
    /**
     * @var MockInterface|ApplicantForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new ApplicantController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ApplicantForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\ApplicantForm', ['lpa' => $this->lpa])->andReturn($this->form);
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
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with(['whoIsRegistering' => $this->lpa->document->whoIsRegistering])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionGetMultiplePrimaryAttorneysJointly()
    {
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));
        $this->lpa->document->whoIsRegistering = [1, 2];
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with(['whoIsRegistering' => '1,2'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionGetMultiplePrimaryAttorneysJointlyAndSeverally()
    {
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));
        $this->lpa->document->whoIsRegistering = [1, 2];
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with(['whoIsRegistering' => '1,2,3', 'attorneyList' => $this->lpa->document->whoIsRegistering])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        $postData = [];

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostDonorRegisteringValueNotChanged()
    {
        $response = new Response();

        $postData = [
            'whoIsRegistering' => Correspondence::WHO_DONOR
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/applicant');
        $this->redirect->shouldReceive('toRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set applicant for id: 91333263035
     */
    public function testIndexActionPostDonorRegisteringValueChangedException()
    {
        $postData = [
            'whoIsRegistering' => Correspondence::WHO_DONOR
        ];
        $this->lpa->document->whoIsRegistering = [1];

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')->with($this->lpa->id, Correspondence::WHO_DONOR)->andReturn(false);

        $this->controller->indexAction();
    }

    public function testIndexActionPostAttorneyRegisteringJointlyChangedSuccessful()
    {
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));

        $response = new Response();

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3'
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')->with($this->lpa->id, [1])->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/applicant');
        $this->redirect->shouldReceive('toRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostAttorneyRegisteringJointlyAndSeverallyChangedSuccessful()
    {
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));

        $response = new Response();

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3'
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->form->shouldReceive('setData')->with($postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')->with($this->lpa->id, '1,2,3')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/applicant');
        $this->redirect->shouldReceive('toRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}