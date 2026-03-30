<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use Application\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HowPrimaryAttorneysMakeDecisionHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ApplicantService&MockObject $applicantService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    /** @var HowAttorneysMakeDecisionForm&MockObject */
    private $form;
    private HowPrimaryAttorneysMakeDecisionHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->form = $this->createMock(HowAttorneysMakeDecisionForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new HowPrimaryAttorneysMakeDecisionHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
        );
    }

    private function createLpa(
        ?string $how = null,
        ?string $howDetails = null,
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();

        $decisions = new PrimaryAttorneyDecisions();
        $decisions->how = $how;
        $decisions->howDetails = $howDetails;
        $lpa->document->primaryAttorneyDecisions = $decisions;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/replacement-attorney');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/how-primary-attorneys-make-decision');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetBindsExistingDecisionsAndRendersForm(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostSetsValidationGroupWhenNotDepends(): void
    {
        $this->form
            ->expects($this->once())
            ->method('setValidationGroup')
            ->with(['how']);

        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('rendered-html');

        $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ])
        );
    }

    public function testPostDoesNotSetValidationGroupWhenDepends(): void
    {
        $this->form
            ->expects($this->never())
            ->method('setValidationGroup');

        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('rendered-html');

        $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            ])
        );
    }

    public function testPostValidValueUnchangedSkipsSaveAndRedirects(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->replacementAttorneyCleanup
            ->expects($this->never())
            ->method('cleanUp');

        $this->applicantService
            ->expects($this->never())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/replacement-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            '/lpa/91333263035/replacement-attorney',
            $response->getHeaderLine('Location')
        );
    }

    public function testPostValidValueChangedSavesAndRedirects(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->applicantService
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/replacement-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidDependsWithDetailsChangedSavesAndRedirects(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails' => 'Some details about decisions',
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->applicantService
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/replacement-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
                'howDetails' => 'Some details about decisions',
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidDependsWithoutDetailsStripsHowDetails(): void
    {
        $lpa = $this->createLpa(
            PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'Old details'
        );

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->with(
                $this->anything(),
                $this->callback(function (PrimaryAttorneyDecisions $decisions) {
                    return $decisions->how === PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
                        && $decisions->howDetails === null;
                })
            )
            ->willReturn(true);

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/replacement-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ]);

        $this->lpaApplicationService
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set primary attorney decisions for id: 91333263035'
        );

        $this->handler->handle(
            $this->createRequest('POST', [
                'how' => PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
            ], $lpa)
        );
    }

    /**
     * @dataProvider howDetailsUnchangedProvider
     */
    public function testPostValidDetailsUnchangedSkipsSave(
        string $existingHow,
        ?string $existingHowDetails,
        string $postHow,
        ?string $postHowDetails,
    ): void {
        $lpa = $this->createLpa($existingHow, $existingHowDetails);

        $formData = ['how' => $postHow];
        if ($postHowDetails !== null) {
            $formData['howDetails'] = $postHowDetails;
        }

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/replacement-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string, 3: string|null}>
     */
    public static function howDetailsUnchangedProvider(): array
    {
        return [
            'jointly unchanged' => [
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
                null,
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
                null,
            ],
            'jointly-and-severally unchanged' => [
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                null,
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                null,
            ],
            'depends with same details unchanged' => [
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
                'Some details',
                PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
                'Some details',
            ],
        ];
    }
}
