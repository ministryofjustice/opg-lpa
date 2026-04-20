<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CheckoutIndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Submit;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private CheckoutIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new CheckoutIndexHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
        );
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment = new Payment();
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createIncompleteLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->payment = new Payment();

        return $lpa;
    }

    private function createRequest(
        string $method,
        Lpa $lpa,
        bool $lpaComplete = true,
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($lpaComplete ? 'lpa/checkout' : 'lpa/other');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->id . '/checkout', $method))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout');

        if ($method === 'POST') {
            $request = $request->withParsedBody([]);
        }

        return $request;
    }

    private function mockBlankForm(): void
    {
        $submitElement = $this->createMock(Submit::class);
        $submitElement->method('setAttribute')->willReturnSelf();

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->with('submit')->willReturn($submitElement);

        $this->formElementManager->method('get')->willReturn($form);
    }

    public function testGetRendersForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankForm();

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/checkout/index.twig')
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithIncompleteLpaStillRendersForm(): void
    {
        $lpa = $this->createIncompleteLpa();
        $this->mockBlankForm();

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $lpa, false));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createIncompleteLpa();

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/more-info-required', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/more-info-required');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testPostWithCompleteLpaRendersForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankForm();

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
