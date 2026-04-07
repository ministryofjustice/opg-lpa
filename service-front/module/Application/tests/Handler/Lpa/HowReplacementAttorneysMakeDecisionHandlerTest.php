<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HowReplacementAttorneysMakeDecisionHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var \Application\Form\Lpa\HowAttorneysMakeDecisionForm&MockObject */
    private $form;
    private HowReplacementAttorneysMakeDecisionHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\HowAttorneysMakeDecisionForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new HowReplacementAttorneysMakeDecisionHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(?ReplacementAttorneyDecisions $decisions = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->replacementAttorneyDecisions = $decisions;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/certificate-provider');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/how-replacement-attorneys-make-decision');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    #[Test]
    public function getWithNoExistingDecisionsRendersFormWithoutBind(): void
    {
        $lpa = $this->createLpa();

        $this->form
            ->expects($this->never())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function getWithExistingDecisionsBindsAndRendersForm(): void
    {
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->how = ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa = $this->createLpa($decisions);

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function postInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            ])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[Test]
    public function postValidValueUnchangedSkipsSaveAndRedirects(): void
    {
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->how = ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $decisions->howDetails = null;

        $lpa = $this->createLpa($decisions);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setReplacementAttorneyDecisions');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            '/lpa/91333263035/certificate-provider',
            $response->getHeaderLine('Location')
        );
    }

    #[Test]
    public function postValidValueChangedSavesAndRedirects(): void
    {
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->how = ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;
        $decisions->howDetails = null;

        $lpa = $this->createLpa($decisions);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function postValidCreatesNewDecisionsWhenNoneExist(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function postValidWithDependsSavesHowDetails(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails' => 'Custom decision instructions',
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->with(
                $this->anything(),
                $this->callback(function (ReplacementAttorneyDecisions $d): bool {
                    return $d->how === ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS
                        && $d->howDetails === 'Custom decision instructions';
                })
            )
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
                'howDetails' => 'Custom decision instructions',
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function postValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa();

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set replacement attorney decisions for id: 91333263035'
        );

        $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function decisionTypesValidationGroupProvider(): array
    {
        return [
            'jointly sets validation group' => [
                ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
                true,
            ],
            'jointly and severally sets validation group' => [
                ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                true,
            ],
            'depends does not set validation group' => [
                ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('decisionTypesValidationGroupProvider')]
    public function setsValidationGroupCorrectlyForDecisionType(
        string $how,
        bool $expectsValidationGroup,
    ): void {
        $lpa = $this->createLpa();
        $postData = ['how' => $how];

        if ($how === ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $postData['howDetails'] = 'Some details';
        }

        if ($expectsValidationGroup) {
            $this->form->expects($this->once())->method('setValidationGroup')->with(['how']);
        } else {
            $this->form->expects($this->never())->method('setValidationGroup');
        }

        $this->form->method('setData');
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);
        $this->lpaApplicationService->method('setReplacementAttorneyDecisions')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $this->handler->handle($this->createRequest('POST', $postData, $lpa));
    }

    #[Test]
    public function postNonDependsClearsHowDetails(): void
    {
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->how = ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS;
        $decisions->howDetails = 'Old details';

        $lpa = $this->createLpa($decisions);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->with(
                $this->anything(),
                $this->callback(function (ReplacementAttorneyDecisions $d): bool {
                    return $d->how === ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
                        && $d->howDetails === null;
                })
            )
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'how' => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
