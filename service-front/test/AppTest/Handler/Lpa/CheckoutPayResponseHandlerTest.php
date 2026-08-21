<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
use App\Handler\Lpa\CheckoutPayResponseHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\CardPayments;
use App\Service\Payment\Helper\CheckoutHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Submit;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutPayResponseHandlerTest extends TestCase
{
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private GovPayClient&MockObject $paymentClient;
    private UrlHelper&MockObject $urlHelper;
    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private CheckoutHelper&MockObject $checkoutHelper;
    private CardPayments&MockObject $cardPayments;
    private CheckoutPayResponseHandler $handler;

    protected function setUp(): void
    {
        $this->formElementManager    = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService  = $this->createMock(Communication::class);
        $this->paymentClient         = $this->createMock(GovPayClient::class);
        $this->urlHelper             = $this->createMock(UrlHelper::class);
        $this->renderer              = $this->createMock(TemplateRendererInterface::class);
        $this->logger                = $this->createMock(LoggerInterface::class);
        $this->checkoutHelper        = $this->createMock(CheckoutHelper::class);
        $this->cardPayments          = $this->createMock(CardPayments::class);

        $this->handler = new CheckoutPayResponseHandler(
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->paymentClient,
            $this->urlHelper,
            $this->renderer,
            $this->logger,
            $this->cardPayments,
            $this->checkoutHelper,
        );
    }

    private function makeGovPayPayment(array $data): GovPayPayment
    {
        return new GovPayPayment((array) json_decode((string) json_encode($data)));
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(new Payment());
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createRequest(Lpa $lpa): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn('lpa/checkout');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->getId() . '/checkout/pay/response', 'GET'))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/pay/response');
    }

    private function mockFailureForm(): void
    {
        $submitElement = $this->createMock(Submit::class);
        $submitElement->method('setAttribute')->willReturnSelf();

        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->with('submit')->willReturn($submitElement);

        $this->formElementManager->method('get')->willReturn($form);
    }

    public function testThrowsWhenNoGatewayReference(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment id needed');

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testNullPaymentResponseRedirectsToPayPage(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('ref-123');

        $this->paymentClient->method('getPayment')->willReturn(null);
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/checkout/pay');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('checkout/pay', $response->getHeaderLine('location'));
    }

    public static function failureTemplateProvider(): array
    {
        return [
            'cancelled (P0030)' => ['P0030', 'application/authenticated/lpa/checkout/govpay-cancel.twig'],
            'other failure'     => ['P0050', 'application/authenticated/lpa/checkout/govpay-failure.twig'],
            'no code (null)'    => [null,    'application/authenticated/lpa/checkout/govpay-failure.twig'],
        ];
    }

    #[DataProvider('failureTemplateProvider')]
    public function testUnsuccessfulPaymentRendersCorrectTemplate(?string $stateCode, string $template): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('ref-123');

        $this->mockFailureForm();

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'state'      => ['status' => 'failed', 'finished' => true, 'code' => $stateCode],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay', ['lpa-id' => $lpa->getId()])
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
        $lpa->getPayment()->setGatewayReference('ref-123');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'reference'  => 'txn-ref',
            'email'      => 'user@EXAMPLE.com',
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->cardPayments->expects($this->once())
            ->method('recordSuccessfulPayment')
            ->with($lpa, $govPayPayment)
            ->willReturn(true);
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/91333263035/complete'));

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }

    public static function whitespaceEmailProvider(): array
    {
        return [
            'trailing space'         => ['user@example.com ', 'user@example.com'],
            'leading space'          => [' user@example.com', 'user@example.com'],
            'surrounding whitespace' => ["\tuser@example.com\n", 'user@example.com'],
            'mixed case with space'  => ['User@Example.COM ', 'user@example.com'],
        ];
    }

    #[DataProvider('whitespaceEmailProvider')]
    public function testGovPayEmailIsTrimmedAndLowercasedBeforePersisting(string $govPayEmail, string $expected): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('ref-123');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'reference'  => 'txn-ref',
            'email'      => $govPayEmail,
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->cardPayments->expects($this->once())
            ->method('recordSuccessfulPayment')
            ->with($lpa, $govPayPayment)
            ->willReturnCallback(function ($lpa, $payment) {
                $govPayEmail = $payment->email ?? null;
                $email = is_string($govPayEmail) && trim($govPayEmail) !== ''
                    ? new EmailAddress(['address' => strtolower(trim($govPayEmail))])
                    : null;
                $lpa->getPayment()->setEmail($email);
                $lpa->getPayment()->setMethod(Payment::PAYMENT_TYPE_CARD);
                $lpa->getPayment()->setReference($payment->reference);
                return true;
            });
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/' . $lpa->getId() . '/complete'));

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, (string) $lpa->getPayment()->getEmail());
    }

    public function testMalformedEmailFromGovPayIsPassedThroughUnchanged(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('ref-123');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'ref-123',
            'reference'  => 'txn-ref',
            'email'      => 'not-a-valid-email',
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->cardPayments->expects($this->once())
            ->method('recordSuccessfulPayment')
            ->with($lpa, $govPayPayment)
            ->willReturnCallback(function ($lpa, $payment) {
                $govPayEmail = $payment->email ?? null;
                $email = is_string($govPayEmail) && trim($govPayEmail) !== ''
                    ? new EmailAddress(['address' => strtolower(trim($govPayEmail))])
                    : null;
                $lpa->getPayment()->setEmail($email);
                $lpa->getPayment()->setMethod(Payment::PAYMENT_TYPE_CARD);
                $lpa->getPayment()->setReference($payment->reference);
                return true;
            });
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/' . $lpa->getId() . '/complete'));

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('not-a-valid-email', (string) $lpa->getPayment()->getEmail());
    }
}
