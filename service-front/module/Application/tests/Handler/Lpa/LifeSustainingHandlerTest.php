<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\LifeSustainingHandler;
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

class LifeSustainingHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var \Application\Form\Lpa\LifeSustainingForm&MockObject */
    private $form;
    private LifeSustainingHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\LifeSustainingForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new LifeSustainingHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(?bool $canSustainLife = null, bool $withDecisions = true): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();

        if ($withDecisions) {
            $decisions = new PrimaryAttorneyDecisions();
            $decisions->canSustainLife = $canSustainLife;
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
        $lpa = $lpa ?? $this->createLpa(true);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/primary-attorney');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/life-sustaining');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithExistingDecisionsBindsAndRendersForm(): void
    {
        $lpa = $this->createLpa(true);

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
            $this->createRequest('POST', ['canSustainLife' => '1'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidValueUnchangedSkipsSaveAndRedirects(): void
    {
        $lpa = $this->createLpa(true);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['canSustainLife' => '1']);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPrimaryAttorneyDecisions');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['canSustainLife' => '1'], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/primary-attorney', $response->getHeaderLine('Location'));
    }

    public function testPostValidValueChangedSavesAndRedirects(): void
    {
        $lpa = $this->createLpa(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['canSustainLife' => '1']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['canSustainLife' => '1'], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidCreatesNewDecisionsWhenNoneExist(): void
    {
        $lpa = $this->createLpa(null, false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['canSustainLife' => '1']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['canSustainLife' => '1'], $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['canSustainLife' => '1']);

        $this->lpaApplicationService
            ->method('setPrimaryAttorneyDecisions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set life sustaining for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', ['canSustainLife' => '1'], $lpa)
        );
    }
}
