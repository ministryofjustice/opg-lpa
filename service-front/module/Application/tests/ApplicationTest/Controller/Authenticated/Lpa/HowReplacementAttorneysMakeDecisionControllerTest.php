<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController;
use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class HowReplacementAttorneysMakeDecisionControllerTest extends AbstractControllerTest
{
    /**
     * @var HowReplacementAttorneysMakeDecisionController
     */
    private $controller;
    /**
     * @var MockInterface|HowAttorneysMakeDecisionForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postData = [
        'how' => null,
        'howDetails' => null
    ];

    public function setUp()
    {
        $this->controller = new HowReplacementAttorneysMakeDecisionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(HowAttorneysMakeDecisionForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\HowAttorneysMakeDecisionForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
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
        $this->form->shouldReceive('bind')
            ->withArgs([$this->lpa->document->replacementAttorneyDecisions->flatten()])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostInvalid($this->form, $this->postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['how'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostNotChanged()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['how'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/how-replacement-attorneys-make-decision');
        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set replacement attorney decisions for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $response = new Response();

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['how'])->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpaId, $replacementAttorneyDecisions) {
                return $lpaId === $this->lpa->id
                    && $replacementAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                    && $replacementAttorneyDecisions->howDetails == null;
            })->andReturn(false)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_DEPENDS;
        $postData['howDetails'] = 'Details';

        $this->lpa->document->replacementAttorneyDecisions = null;
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->twice();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpaId, $replacementAttorneyDecisions) {
                return $lpaId === $this->lpa->id
                    && $replacementAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                    && $replacementAttorneyDecisions->howDetails == 'Details';
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/how-replacement-attorneys-make-decision');
        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
