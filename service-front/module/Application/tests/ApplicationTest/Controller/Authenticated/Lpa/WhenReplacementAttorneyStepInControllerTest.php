<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController;
use Application\Form\Lpa\WhenReplacementAttorneyStepInForm;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class WhenReplacementAttorneyStepInControllerTest extends AbstractControllerTest
{
    /**
     * @var WhenReplacementAttorneyStepInController
     */
    private $controller;
    /**
     * @var MockInterface|WhenReplacementAttorneyStepInForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $postDataDepends = [
        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
        'whenDetails' => 'Unit test instruction'
    ];
    private $postDataFirst = [
        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST
    ];
    private $postDataLast = [
        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
    ];

    public function setUp()
    {
        $this->controller = new WhenReplacementAttorneyStepInController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhenReplacementAttorneyStepInForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhenReplacementAttorneyStepInForm', ['lpa' => $this->lpa]])
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
        $this->setPostInvalid($this->form, $this->postDataDepends);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set replacement step in decisions for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['when'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpaId, $replacementAttorneyDecisions) {
                return $lpaId === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['when'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpaId, $replacementAttorneyDecisions) {
                return $lpaId === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([$this->lpa->id])
            ->andReturn($this->lpa)->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['ReplacementAttorneyCleanup'])
            ->andReturn(new ReplacementAttorneyCleanup())->once()->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/when-replacement-attorney-step-in');
        $this->setRedirectToRoute('lpa/how-replacement-attorneys-make-decision', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostSuccessDepends()
    {
        $response = new Response();

        $this->lpa->document->replacementAttorneyDecisions = null;
        $this->controller->setLpa($this->lpa);
        $this->setPostValid($this->form, $this->postDataDepends);
        $this->form->shouldReceive('getData')->andReturn($this->postDataDepends)->twice();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpaId, $replacementAttorneyDecisions) {
                return $lpaId === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataDepends['when']
                    && $replacementAttorneyDecisions->whenDetails === $this->postDataDepends['whenDetails'];
            })->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([$this->lpa->id])
            ->andReturn($this->lpa)->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['ReplacementAttorneyCleanup'])
            ->andReturn(new ReplacementAttorneyCleanup())->once()->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/when-replacement-attorney-step-in');
        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
