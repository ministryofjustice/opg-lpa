<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController;
use Application\Form\Lpa\WhenReplacementAttorneyStepInForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

class WhenReplacementAttorneyStepInControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|WhenReplacementAttorneyStepInForm
     */
    private $form;
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

    public function setUp() : void
    {
        parent::setUp();

        $this->form = Mockery::mock(WhenReplacementAttorneyStepInForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhenReplacementAttorneyStepInForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')
            ->withArgs([$this->lpa->document->replacementAttorneyDecisions->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->setPostInvalid($this->form, $this->postDataDepends);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostFailed()
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['when'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set replacement step in decisions for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $response = new Response();

        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['when'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/when-replacement-attorney-step-in');
        $this->setRedirectToRoute('lpa/how-replacement-attorneys-make-decision', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostSuccessDepends()
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $response = new Response();

        $this->lpa->document->replacementAttorneyDecisions = null;
        $this->setPostValid($this->form, $this->postDataDepends);
        $this->form->shouldReceive('getData')->andReturn($this->postDataDepends)->twice();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions) {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataDepends['when']
                    && $replacementAttorneyDecisions->whenDetails === $this->postDataDepends['whenDetails'];
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/when-replacement-attorney-step-in');
        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
