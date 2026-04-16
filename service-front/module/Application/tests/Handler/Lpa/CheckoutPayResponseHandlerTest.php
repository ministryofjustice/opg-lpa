<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Alphagov\Pay\Client as GovPayClient;
use Alphagov\Pay\Response\Payment as GovPayPayment;
use Application\Handler\Lpa\CheckoutPayResponseHandler;
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
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CheckoutPayResponseHandlerTest extends TestCase
{
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private GovPayClient&MockObject $paymentClient;
    private MvcUrlHelper&MockObject $urlHelper;
    private TemplateRendererInterface&MockObject $renderer;
    private CheckoutPayResponseHandler $handler;

    protected function setUp(): void
    {
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->paymentClient = $this->createMock(GovPayClient::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->handler = new CheckoutPayResponseHandler(
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->paymentClient,
            $this->urlHelper,
            $this->renderer,
        );
    }

    /**
     * Creates a GovPayPayment with stdClass nested objects, matching how the real Pay client builds them.
     *
     * @param array<string, mixed> $data
     */
    private function makeGovPayPayment(array $data): GovPayPayment
    {
        return new GovPayPayment((array) json_decode((string) json_encode($data)));
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment = new Payment();
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createRequest(Lpa $lpa): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn('lpa/checkout');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->id . '/checkout/pay/response', 'GET'))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/pay/response');
    }

    public function testThrowsWhenNoGatewayReference(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment id needed');

        $this->handler->handle($this->createRequest($lpa));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function failureTemplateProvider(): array
    {
        return [
            'cancelled (P0030)' => ['P0030', 'application/authenticated/lpa/checkout/govpay-cancel.twig'],
            'other failure'     => ['P0050', 'application/authenticated/lpa/checkout/govpay-failure.twig'],
        ];
    }

    #[DataProvider('failureTemplateProvider')]
    public function testUnsuccessfulPaymentRendersCorrectTemplate(string $stateCode, string $template): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'ref-123';

        $submitElement = $this->createMock(Submit::class);
        $submitElement->method('setAttribute')->willReturnSelf();
        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->with('submit')->willReturn($submitElement);
        $this->formElementManager->method('get')->willReturn($form);

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'state' => ['status' => 'failed', 'finished' => true, 'code' => $stateCode],
            '_links' => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->with($template)
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulPaymentRecordsDetailsAndFinishesCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'ref-123';

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'reference' => 'txn-ref',
            'email' => 'user@EXAMPLE.com',
            'state' => ['status' => 'success', 'finished' => true],
            '_links' => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->lpaApplicationService->expects($this->once())->method('lockLpa');
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail');
        $this->urlHelper->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }
}
