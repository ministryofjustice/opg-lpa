<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\WhenLpaStartsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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

class WhenLpaStartsHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var \Application\Form\Lpa\WhenLpaStartsForm&MockObject */
    private $form;
    private WhenLpaStartsHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\WhenLpaStartsForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new WhenLpaStartsHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(?string $when = null, bool $withDecisions = true): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();

        if ($withDecisions) {
            $decisions = new PrimaryAttorneyDecisions();
            $decisions->when = $when;
            $lpa->document->primaryAttorneyDecisions = $decisions;
        } else {
            $lpa->document->primaryAttorneyDecisions = null;
        }

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/primary-attorney');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/when-lpa-starts');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithExistingDecisionsBindsAndRendersForm(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW);

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithNoExistingDecisionsRendersFormWithoutBind(): void
    {
        $lpa = $this->createLpa(null, false);

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
            $this->createRequest('POST', ['when' => ''])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidValueUnchangedSkipsSaveAndRedirects(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
        ]);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $response->getHeaderLine('Location'));
    }

    public function testPostValidValueChangedSavesAndRedirects(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidCreatesNewDecisionsWhenNoneExist(): void
    {
        $lpa = $this->createLpa(null, false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
            ], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
        ]);

        $this->lpaApplicationService
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set when LPA starts for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', [
                'when' => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
            ], $lpa)
        );
    }
}
