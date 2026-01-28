<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ApplicantController;
use Application\Form\Lpa\ApplicantForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class ApplicantControllerTest extends AbstractControllerTestCase
{
    private MockInterface|ApplicantForm $form;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(ApplicantForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\ApplicantForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')
            ->withArgs([['whoIsRegistering' => $this->lpa->document->whoIsRegistering]])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionGetMultiplePrimaryAttorneysJointly(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));
        $this->lpa->document->whoIsRegistering = [1, 2];
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([['whoIsRegistering' => '1,2']])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionGetMultiplePrimaryAttorneysJointlyAndSeverally(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));
        $this->lpa->document->whoIsRegistering = [1, 2];
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([
            ['whoIsRegistering' => '1,2,3', 'attorneyList' => $this->lpa->document->whoIsRegistering]
        ])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostDonorRegisteringValueNotChanged(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        $postData = [
            'whoIsRegistering' => Correspondence::WHO_DONOR
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;

        $this->setPostValid($this->form, $postData);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/applicant');
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $location);
    }

    public function testIndexActionPostDonorRegisteringValueChangedException(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        $postData = [
            'whoIsRegistering' => Correspondence::WHO_DONOR
        ];
        $this->lpa->document->whoIsRegistering = [1];

        $this->setPostValid($this->form, $postData);
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')
            ->withArgs([$this->lpa, Correspondence::WHO_DONOR])->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set applicant for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostAttorneyRegisteringJointlyChangedSuccessful(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3'
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')
            ->withArgs([$this->lpa, [1]])->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/applicant');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $location);
    }

    public function testIndexActionPostAttorneyRegisteringJointlyAndSeverallyChangedSuccessful(): void
    {
        /** @var ApplicantController $controller */
        $controller = $this->getController(ApplicantController::class);

        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($this->lpa->document->primaryAttorneys));

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3'
        ];
        $this->lpa->document->whoIsRegistering = Correspondence::WHO_DONOR;
        $this->lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;

        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoIsRegistering')
            ->withArgs([$this->lpa, '1,2,3'])->andReturn(true);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/applicant');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());

        $location = $result->getHeaders()->get('Location')->getUri();
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $location);
    }
}
