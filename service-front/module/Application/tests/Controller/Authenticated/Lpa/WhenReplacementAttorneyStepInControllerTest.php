<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController;
use Application\Form\Lpa\WhenReplacementAttorneyStepInForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class WhenReplacementAttorneyStepInControllerTest extends AbstractControllerTestCase
{
    private MockInterface|WhenReplacementAttorneyStepInForm $form;
    private array $postDataDepends = [
        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
        'whenDetails' => 'Unit test instruction'
    ];
    private array $postDataLast = [
        'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(WhenReplacementAttorneyStepInForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhenReplacementAttorneyStepInForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    public function testIndexActionGet(): void
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

    public function testIndexActionPostInvalid(): void
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

    public function testIndexActionPostFailed(): void
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['when']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set replacement step in decisions for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->setPostValid($this->form, $this->postDataLast);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['when']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataLast)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataLast['when'];
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/when-replacement-attorney-step-in');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/how-replacement-attorneys-make-decision', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostSuccessDepends(): void
    {
        /** @var WhenReplacementAttorneyStepInController $controller */
        $controller = $this->getController(WhenReplacementAttorneyStepInController::class);

        $this->lpa->document->replacementAttorneyDecisions = null;
        $this->setPostValid($this->form, $this->postDataDepends);
        $this->form->shouldReceive('getData')->andReturn($this->postDataDepends)->twice();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->when === $this->postDataDepends['when']
                    && $replacementAttorneyDecisions->whenDetails === $this->postDataDepends['whenDetails'];
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/when-replacement-attorney-step-in');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/certificate-provider', $result->getHeaders()->get('Location')->getUri());
    }
}
