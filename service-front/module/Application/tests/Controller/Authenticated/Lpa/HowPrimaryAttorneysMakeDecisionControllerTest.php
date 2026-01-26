<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController;
use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use Application\Model\Service\Lpa\Applicant;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class HowPrimaryAttorneysMakeDecisionControllerTest extends AbstractControllerTestCase
{
    private MockInterface|HowAttorneysMakeDecisionForm $form;
    private array $postData = [
        'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        'howDetails' => 'Details'
    ];
    /**
     * @var MockInterface|Applicant
     */
    private $applicantService;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(HowAttorneysMakeDecisionForm::class);

        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\HowAttorneysMakeDecisionForm', ['lpa' => $this->lpa]])
            ->andReturn($this->form);
    }

    protected function getController(string $controllerName)
    {
        /** @var HowPrimaryAttorneysMakeDecisionController $controller */
        $controller = parent::getController($controllerName);

        $this->applicantService = Mockery::mock(Applicant::class);
        $controller->setApplicantService($this->applicantService);

        return $controller;
    }

    public function testIndexActionGet(): void
    {
        $controller = $this->getController(HowPrimaryAttorneysMakeDecisionController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')
            ->withArgs([$this->lpa->document->primaryAttorneyDecisions->flatten()])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid(): void
    {
        $controller = $this->getController(HowPrimaryAttorneysMakeDecisionController::class);

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
        $controller = $this->getController(HowPrimaryAttorneysMakeDecisionController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['how']])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/how-primary-attorneys-make-decision');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/replacement-attorney', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionPostFailed(): void
    {
        $controller = $this->getController(HowPrimaryAttorneysMakeDecisionController::class);

        $response = new Response();

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('setValidationGroup')->withArgs([['how']])->once();
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY
                    && $primaryAttorneyDecisions->howDetails == null;
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set primary attorney decisions for id: 91333263035');

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionPostSuccess(): void
    {
        $controller = $this->getController(HowPrimaryAttorneysMakeDecisionController::class);

        $postData = $this->postData;
        $postData['how'] = AbstractDecisions::LPA_DECISION_HOW_DEPENDS;

        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->twice();
        $this->lpaApplicationService->shouldReceive('setPrimaryAttorneyDecisions')
            ->withArgs(function ($lpa, $primaryAttorneyDecisions): bool {
                return $lpa->id === $this->lpa->id
                    && $primaryAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_DEPENDS
                    && $primaryAttorneyDecisions->howDetails == 'Details';
            })->andReturn(true)->once();
        $this->replacementAttorneyCleanup->shouldReceive('cleanUp')->andReturn(true);
        $this->applicantService->shouldReceive('cleanUp')->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/how-primary-attorneys-make-decision');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/replacement-attorney', $result->getHeaders()->get('Location')->getUri());
    }
}
