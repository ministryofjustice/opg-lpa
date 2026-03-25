<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WhenReplacementAttorneyStepInHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var \Application\Form\Lpa\WhenReplacementAttorneyStepInForm&MockObject */
    private $form;
    private WhenReplacementAttorneyStepInHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\WhenReplacementAttorneyStepInForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new WhenReplacementAttorneyStepInHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->replacementAttorneyCleanup,
            $this->urlHelper,
        );
    }

    private function createLpa(
        ?string $when = null,
        ?string $whenDetails = null,
        bool $withDecisions = true
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();

        if ($withDecisions) {
            $decisions = new ReplacementAttorneyDecisions();
            $decisions->when = $when;
            $decisions->whenDetails = $whenDetails;
            $lpa->document->replacementAttorneyDecisions = $decisions;
        } else {
            $lpa->document->replacementAttorneyDecisions = null;
        }

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa(
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
        );

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-replacement-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/when-replacement-attorney-step-in');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithExistingDecisionsBindsAndRendersForm(): void
    {
        $lpa = $this->createLpa(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithNoExistingDecisionsRendersFormWithoutBind(): void
    {
        $lpa = $this->createLpa(null, null, false);

        $this->form
            ->expects($this->never())
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
            $this->createRequest('POST', ['when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidNonDependsSetsValidationGroupToWhenOnly(): void
    {
        $lpa = $this->createLpa(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);

        $this->form
            ->expects($this->once())
            ->method('setValidationGroup')
            ->with(['when']);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setReplacementAttorneyDecisions');

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/how-replacement-attorneys-make-decision');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidValueUnchangedSkipsSaveAndRedirects(): void
    {
        $lpa = $this->createLpa(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setReplacementAttorneyDecisions');

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/how-replacement-attorneys-make-decision');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            '/lpa/91333263035/how-replacement-attorneys-make-decision',
            $response->getHeaderLine('Location')
        );
    }

    public function testPostValidValueChangedSavesAndRedirects(): void
    {
        $lpa = $this->createLpa(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/how-replacement-attorneys-make-decision');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidCreatesNewDecisionsWhenNoneExist(): void
    {
        $lpa = $this->createLpa(null, null, false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/how-replacement-attorneys-make-decision');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidDependsIncludesWhenDetails(): void
    {
        $lpa = $this->createLpa(null, null, false);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/certificate-provider');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/when-replacement-attorney-step-in')
            ->withParsedBody([
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
                'whenDetails' => 'Unit test instruction',
            ]);

        $this->form
            ->expects($this->never())
            ->method('setValidationGroup');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
            'whenDetails' => 'Unit test instruction',
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->with(
                $this->callback(function (Lpa $lpa) {
                    return $lpa->id === 91333263035;
                }),
                $this->callback(function (ReplacementAttorneyDecisions $decisions) {
                    return $decisions->when === ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
                        && $decisions->whenDetails === 'Unit test instruction';
                })
            )
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            '/lpa/91333263035/certificate-provider',
            $response->getHeaderLine('Location')
        );
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        $this->lpaApplicationService
            ->method('setReplacementAttorneyDecisions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set replacement step in decisions for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', [
                'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ], $lpa)
        );
    }
}
