<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
use App\Handler\Lpa\CheckoutPayHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use App\Service\Payment\CardPayments;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutPayHandlerTest extends TestCase
{
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private GovPayClient&MockObject $paymentClient;
    private UrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private CheckoutHelper&MockObject $checkoutHelper;
    private CardPayments&MockObject $cardPayments;
    private CheckoutPayHandler $handler;

    protected function setUp(): void
    {
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->paymentClient = $this->createMock(GovPayClient::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);
        $this->cardPayments = $this->createMock(CardPayments::class);

        $this->handler = new CheckoutPayHandler(
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->paymentClient,
            $this->urlHelper,
            $this->cardPayments,
            $this->logger,
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

    private function createIncompleteLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->setId(91333263035);
        $lpa->setDocument(new Document());
        $lpa->setPayment(new Payment());

        return $lpa;
    }

    private function createRequest(
        string $method,
        Lpa $lpa,
        bool $lpaComplete = true,
        array $postData = [],
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($lpaComplete ? 'lpa/checkout' : 'lpa/other');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->getId() . '/checkout/pay', $method))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/pay');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function mockBlankFormInvalid(): void
    {
        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);
    }

    private function mockBlankFormValid(): void
    {
        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $this->formElementManager->method('get')->willReturn($form);
    }

    public function testIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createIncompleteLpa();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(false);
        $this->checkoutHelper->method('redirectToMoreInfoRequired')
            ->willReturn(new RedirectResponse('/lpa/91333263035/more-info-required'));

        $response = $this->handler->handle($this->createRequest('GET', $lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testPostWithInvalidFormRedirectsToCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankFormInvalid();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')
            ->with('lpa/checkout', ['lpa-id' => $lpa->getId()], [])
            ->willReturn('/lpa/91333263035/checkout');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, true, ['some' => 'data']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('checkout', $response->getHeaderLine('location'));
    }

    public function testNoExistingGatewayReferenceCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankFormValid();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/pay']],
        ]);

        $this->paymentClient->expects($this->once())->method('createPayment')->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('pay.gov.uk', $response->getHeaderLine('location'));
    }

    public function testExistingGatewayReferenceNullThrowsException(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');
        $this->paymentClient->method('getPayment')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GovPay payment reference: existing-ref');

        $this->handler->handle($this->createRequest('GET', $lpa));
    }

    public function testExistingSuccessfulPaymentRecordsAndFinishesCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference' => 'ref-123',
            'email' => 'user@example.com ',
            'state' => ['status' => 'success', 'finished' => true],
            '_links' => [],
        ]);

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/91333263035/complete'));

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

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

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
    public function testExistingSuccessfulPaymentTrimsAndLowercasesEmail(string $govPayEmail, string $expected): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference'  => 'ref-123',
            'email'      => $govPayEmail,
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/' . $lpa->getId() . '/complete'));

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

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, (string) $lpa->getPayment()->getEmail());
    }

    public static function absentEmailProvider(): array
    {
        return [
            'empty string'    => [''],
            'whitespace only' => ['   '],
            'null'            => [null],
        ];
    }

    #[DataProvider('absentEmailProvider')]
    public function testExistingSuccessfulPaymentWithAbsentEmailRecordsWithNull(mixed $govPayEmail): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();

        $paymentData = [
            'payment_id' => 'existing-ref',
            'reference'  => 'ref-123',
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ];
        if ($govPayEmail !== null) {
            $paymentData['email'] = $govPayEmail;
        }

        $govPayPayment = $this->makeGovPayPayment($paymentData);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/' . $lpa->getId() . '/complete'));

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

        $this->logger->method('info');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNull($lpa->getPayment()->getEmail());
    }

    public function testExistingSuccessfulPaymentWithMalformedEmailIsPassedThrough(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference'  => 'ref-123',
            'email'      => 'not-a-valid-email',
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/' . $lpa->getId() . '/complete'));

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

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('not-a-valid-email', (string) $lpa->getPayment()->getEmail());
    }

    public function testExistingUnfinishedPaymentRedirectsToPaymentPage(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->mockBlankFormValid();
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'state' => ['status' => 'started', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/existing']],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->cardPayments->expects($this->never())->method('recordSuccessfulPayment');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/existing', $response->getHeaderLine('location'));
    }

    public function testFinishedUnsuccessfulPaymentCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('finished-ref');

        $this->mockBlankFormValid();
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('verifyLpaPaymentAmount');

        $existingPayment = $this->makeGovPayPayment([
            'payment_id' => 'finished-ref',
            'state' => ['status' => 'failed', 'finished' => true],
            '_links' => [],
        ]);
        $this->paymentClient->method('getPayment')->willReturn($existingPayment);

        $newPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/new']],
        ]);
        $this->paymentClient->expects($this->once())->method('createPayment')->willReturn($newPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->cardPayments->expects($this->never())->method('recordSuccessfulPayment');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/new', $response->getHeaderLine('location'));
    }
}
