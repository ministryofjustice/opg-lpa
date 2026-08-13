<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\CheckoutIndexHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use App\Service\Payment\CardPayments;
use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Exception\PayException;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
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
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckoutIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private UrlHelper&MockObject $urlHelper;
    private GovPayClient&MockObject $paymentClient;
    private LoggerInterface&MockObject $logger;
    private CheckoutHelper&MockObject $checkoutHelper;
    private CheckoutIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);

        $this->paymentClient = $this->createMock(GovPayClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new CheckoutIndexHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
            new CardPayments(
                $this->paymentClient,
                $this->lpaApplicationService,
                $this->logger,
            ),
            $this->checkoutHelper,
        );
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment = new Payment();
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createStrandedPaymentLpa(): Lpa
    {
        $lpa = $this->createCompleteLpa();

        $lpa->payment->gatewayReference = 'pay-ref-123';
        $lpa->locked      = false;
        $lpa->lockedAt    = null;
        $lpa->completedAt = null;

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

        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->with('submit')->willReturn($submitElement);

        $this->formElementManager->method('get')->willReturn($form);
    }

    public function testGetRendersForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankForm();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
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

        $this->checkoutHelper->method('isLpaComplete')->willReturn(false);
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

        $this->checkoutHelper->method('isLpaComplete')->willReturn(false);
        $this->checkoutHelper->method('redirectToMoreInfoRequired')
            ->willReturn(new RedirectResponse('/lpa/91333263035/more-info-required'));

        $response = $this->handler->handle($this->createRequest('POST', $lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testPostWithCompleteLpaRendersForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankForm();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGovPayIsNotContactedForAnLpaWithNoPaymentInFlight(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankForm();

        $this->paymentClient->expects($this->never())->method('getPayment');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->method('render')->willReturn('html');

        $this->assertInstanceOf(HtmlResponse::class, $this->handler->handle($this->createRequest('GET', $lpa)));
    }

    public function testAStrandedPaymentIsRecoveredAndTheUserIsSentToTheCompletedLpa(): void
    {
        $lpa = $this->createStrandedPaymentLpa();

        $this->paymentClient->expects($this->once())
            ->method('getPayment')
            ->with('pay-ref-123')
            ->willReturn(new GovPayPayment((array) json_decode((string) json_encode([
                'payment_id' => 'pay-ref-123',
                'reference'  => 'A12345678901', // pragma: allowlist secret
                'email'      => 'payer@example.org',
                'state'      => ['status' => 'success', 'finished' => true],
            ]))));

        $this->lpaApplicationService->expects($this->once())
            ->method('updateApplication')
            ->willReturn($lpa);

        $this->lpaApplicationService->expects($this->once())
            ->method('lockLpa')
            ->with($lpa)
            ->willReturn(true);

        $this->communicationService->expects($this->once())
            ->method('sendRegistrationCompleteEmail')
            ->with($lpa)
            ->willReturn(true);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/' . $lpa->id . '/complete');

        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lpa/' . $lpa->id . '/complete', $response->getHeaderLine('location'));
    }

    public function testAnUnpaidGatewayReferenceStillShowsTheCheckoutPage(): void
    {
        $lpa = $this->createStrandedPaymentLpa();
        $this->mockBlankForm();

        $this->paymentClient->expects($this->once())
            ->method('getPayment')
            ->willReturn(new GovPayPayment((array) json_decode((string) json_encode([
                'payment_id' => 'pay-ref-123',
                'state'      => ['status' => 'created', 'finished' => false],
            ]))));

        $this->lpaApplicationService->expects($this->never())->method('updateApplication');
        $this->lpaApplicationService->expects($this->never())->method('lockLpa');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $this->assertInstanceOf(HtmlResponse::class, $this->handler->handle($this->createRequest('GET', $lpa)));
    }

    public function testAnUnreachableGovPayStillShowsTheCheckoutPage(): void
    {
        $lpa = $this->createStrandedPaymentLpa();
        $this->mockBlankForm();

        $this->paymentClient->method('getPayment')
            ->willThrowException(new PayException('GOV.UK Pay unreachable', 503));

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $this->assertInstanceOf(HtmlResponse::class, $this->handler->handle($this->createRequest('GET', $lpa)));
    }
}
