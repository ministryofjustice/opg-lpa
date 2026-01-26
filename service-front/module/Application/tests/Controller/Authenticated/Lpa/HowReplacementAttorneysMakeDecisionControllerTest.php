<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController;
use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class HowReplacementAttorneysMakeDecisionControllerTest extends AbstractControllerTestCase
{
    private MockInterface|HowAttorneysMakeDecisionForm $form;
    private array $postData = [
        'how' => null,
        'howDetails' => null
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(HowAttorneysMakeDecisionForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\HowAttorneysMakeDecisionForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var HowReplacementAttorneysMakeDecisionController $controller */
        $controller = $this->getController(HowReplacementAttorneysMakeDecisionController::class);

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
        /** @var HowReplacementAttorneysMakeDecisionController $controller */
        $controller = $this->getController(HowReplacementAttorneysMakeDecisionController::class);

        $this->setPostInvalid($this->form, $this->postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['how']])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostNotChanged(): void
    {
        /** @var HowReplacementAttorneysMakeDecisionController $controller */
        $controller = $this->getController(HowReplacementAttorneysMakeDecisionController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['how']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/how-replacement-attorneys-make-decision');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/certificate-provider', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostFailed(): void
    {
        /** @var HowReplacementAttorneysMakeDecisionController $controller */
        $controller = $this->getController(HowReplacementAttorneysMakeDecisionController::class);

        $response = new Response();

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['how']])->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                    && $replacementAttorneyDecisions->howDetails == null;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set replacement attorney decisions for id: 91333263035');

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var HowReplacementAttorneysMakeDecisionController $controller */
        $controller = $this->getController(HowReplacementAttorneysMakeDecisionController::class);

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_DEPENDS;
        $postData['howDetails'] = 'Details';

        $this->lpa->document->replacementAttorneyDecisions = null;
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->twice();
        $this->lpaApplicationService->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs(function ($lpa, $replacementAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $replacementAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                    && $replacementAttorneyDecisions->howDetails == 'Details';
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/how-replacement-attorneys-make-decision');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/certificate-provider', $result->getHeaders()->get('Location')->getUri());
    }
}
