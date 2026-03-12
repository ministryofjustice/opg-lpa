<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ApplicantHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ApplicantHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private FormInterface&MockObject $form;
    private ApplicantHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\ApplicantForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new ApplicantHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(
        mixed $whoIsRegistering = null,
        int $primaryAttorneyCount = 1,
        string $howDecision = AbstractDecisions::LPA_DECISION_HOW_JOINTLY
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
        $lpa->document->primaryAttorneyDecisions->how = $howDecision;

        $lpa->document->primaryAttorneys = [];
        for ($i = 1; $i <= $primaryAttorneyCount; $i++) {
            $attorney = new \stdClass();
            $attorney->id = $i;
            $lpa->document->primaryAttorneys[] = $attorney;
        }

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa(Correspondence::WHO_DONOR);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/correspondent');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/applicant');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }


    public function testGetRequestRendersFormAndBindsStringApplicant(): void
    {
        $lpa = $this->createLpa(Correspondence::WHO_DONOR);

        $this->form
            ->expects($this->once())
            ->method('setData')
            ->with(['whoIsRegistering' => Correspondence::WHO_DONOR]);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetBindsMultiplePrimaryAttorneysJointly(): void
    {
        $lpa = $this->createLpa(
            [1, 2],
            3,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY
        );

        $this->form
            ->expects($this->once())
            ->method('setData')
            ->with(['whoIsRegistering' => '1,2']);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetBindsMultiplePrimaryAttorneysJointlyAndSeverally(): void
    {
        $lpa = $this->createLpa(
            [1, 2],
            3,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        );

        $this->form
            ->expects($this->once())
            ->method('setData')
            ->with([
                'whoIsRegistering' => '1,2,3',
                'attorneyList' => [1, 2],
            ]);

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['whoIsRegistering' => 'invalid'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostDonorRegisteringValueUnchangedSkipsSaveAndRedirects(): void
    {
        $lpa = $this->createLpa(Correspondence::WHO_DONOR);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'whoIsRegistering' => Correspondence::WHO_DONOR,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setWhoIsRegistering');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/correspondent');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'whoIsRegistering' => Correspondence::WHO_DONOR,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $response->getHeaderLine('Location'));
    }

    public function testPostDonorRegisteringValueChangedSaves(): void
    {
        $lpa = $this->createLpa([1]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'whoIsRegistering' => Correspondence::WHO_DONOR,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setWhoIsRegistering')
            ->with($this->isInstanceOf(Lpa::class), Correspondence::WHO_DONOR)
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/correspondent');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'whoIsRegistering' => Correspondence::WHO_DONOR,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostThrowsExceptionWhenApiCallFails(): void
    {
        $lpa = $this->createLpa([1]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'whoIsRegistering' => Correspondence::WHO_DONOR,
        ]);

        $this->lpaApplicationService
            ->method('setWhoIsRegistering')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set applicant for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', [
                'whoIsRegistering' => Correspondence::WHO_DONOR,
            ], $lpa)
        );
    }

    public function testPostAttorneyRegisteringJointlyChangedSuccessful(): void
    {
        $lpa = $this->createLpa(
            Correspondence::WHO_DONOR,
            3,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY
        );

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setWhoIsRegistering')
            ->with($this->isInstanceOf(Lpa::class), ['1'])
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/correspondent');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $response->getHeaderLine('Location'));
    }

    public function testPostAttorneyRegisteringJointlyAndSeverallyChangedSuccessful(): void
    {
        $lpa = $this->createLpa(
            Correspondence::WHO_DONOR,
            3,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        );

        $postData = [
            'whoIsRegistering' => '1',
            'attorneyList' => '1,2,3',
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setWhoIsRegistering')
            ->with($this->isInstanceOf(Lpa::class), '1,2,3')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/correspondent');

        $response = $this->handler->handle(
            $this->createRequest('POST', $postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/correspondent', $response->getHeaderLine('Location'));
    }
}
