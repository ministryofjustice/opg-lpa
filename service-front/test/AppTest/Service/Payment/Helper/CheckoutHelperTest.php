<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\Helper;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\CardPayments;
use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
use App\Service\Payment\Helper\CheckoutHelper;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeSharedTest\DataModel\FixturesData;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutHelperTest extends TestCase
{
    private const int IF_MATCH_VERSION = 5;

    private MockObject&LpaApplicationService $lpaApplicationService;
    private MockObject&Communication $communicationService;
    private MockObject&UrlHelper $urlHelper;
    private MockObject&GovPayClient $paymentClient;
    private MockObject&LoggerInterface $logger;
    private MockObject&CardPayments $cardPayments;
    private CheckoutHelper $helper;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->paymentClient = $this->createMock(GovPayClient::class);
        $this->cardPayments = $this->createMock(CardPayments::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->helper = new CheckoutHelper(
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
            $this->paymentClient,
            $this->cardPayments,
            $this->logger,
        );
    }

    public function testFinishCheckoutLocksSendsEmailAndRedirectsToComplete(): void
    {
        $lpa = $this->createCompleteLpa();
        $request = $this->createRequest($lpa, 'lpa/checkout');

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->helper->finishCheckout($lpa, $request, self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lpa/91333263035/complete', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayByChequeThrowsWhenSetPaymentFails(): void
    {
        $lpa = $this->createCompleteLpa();

        $this->lpaApplicationService->method('setPayment')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set payment details for id: ' . $lpa->getId()
            . ' in App\Service\Payment\Helper\CheckoutHelper'
        );

        $this->helper->confirmAndPayByCheque($lpa, $this->createRequest($lpa, ''), self::IF_MATCH_VERSION);
    }

    public function testConfirmAndPayByChequeAmountChangeResetsGatewayReference(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('old-ref');
        $lpa->getPayment()->setAmount(99999.0);

        $this->logger->expects($this->once())->method('info');

        $twice = $this->exactly(2);
        $this->lpaApplicationService->expects($twice)
            ->method('setPayment')
            ->willReturnCallback(
                function (Lpa $_lpa, Payment $_payment, int $_version) use ($twice, $lpa) {
                    /** @psalm-suppress InternalMethod */
                    switch ($twice->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals($lpa, $_lpa);
                            $this->assertNull($_payment->getGatewayReference());
                            $this->assertEquals(self::IF_MATCH_VERSION, $_version);
                            return true;

                        case 2:
                            $this->assertEquals($lpa, $_lpa);
                            $this->assertNull($_payment->getGatewayReference());
                            $this->assertEquals(self::IF_MATCH_VERSION + 1, $_version);
                            return true;
                    }
                }
            );

        $this->helper->confirmAndPayByCheque($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);
    }

    public function testConfirmAndPayByChequeLocksLpaAndRedirectsToComplete(): void
    {
        $lpa = $this->createCompleteLpa();

        $this->lpaApplicationService->method('setPayment')->willReturn(true);

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION + 1);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->helper->confirmAndPayByCheque($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayByCardNoExistingGatewayReferenceCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/pay']],
        ]);

        $this->paymentClient->expects($this->once())
            ->method('createPayment')
            ->with(
                9200,
                '91333263035',
                'Property and financial affairs LPA for Hon Ayden Armstrong',
                new Uri('https://example.com/lpa/91333263035/checkout/pay/response'),
            )
            ->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('pay.gov.uk', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayByCardPadsIdWhenTooShort(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->setId(1);

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/pay']],
        ]);

        $this->paymentClient->expects($this->once())
            ->method('createPayment')
            ->with(
                9200,
                '00000000001',
                'Property and financial affairs LPA for Hon Ayden Armstrong',
                new Uri('https://example.com/lpa/91333263035/checkout/pay/response'),
            )
            ->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('pay.gov.uk', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayByCardThrowsExceptionWhenLpaIdTooLong(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->setId(123451234512);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA ID is too long');
        $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);
    }

    public function testConfirmAndPayByCardExistingGatewayReferenceNullThrowsException(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $this->paymentClient->method('getPayment')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GovPay payment reference: existing-ref');

        $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);
    }

    public function testConfirmAndPayByCardExistingSuccessfulPaymentRecordsAndFinishesCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference' => 'ref-123',
            'email' => 'user@example.com ',
            'state' => ['status' => 'success', 'finished' => true],
            '_links' => [],
        ]);

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION + 1);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/complete');

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

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

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
    public function testConfirmAndPayByCardExistingSuccessfulPaymentTrimsAndLowercasesEmail(string $govPayEmail, string $expected): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference'  => 'ref-123',
            'email'      => $govPayEmail,
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION + 1);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/' . $lpa->getId() . '/complete');

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

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

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
    public function testConfirmAndPayByCardExistingSuccessfulPaymentWithAbsentEmailRecordsWithNull(mixed $govPayEmail): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

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

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION + 1);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/' . $lpa->getId() . '/complete');

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

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNull($lpa->getPayment()->getEmail());
    }

    public function testConfirmAndPayByCardExistingSuccessfulPaymentWithMalformedEmailIsPassedThrough(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference'  => 'ref-123',
            'email'      => 'not-a-valid-email',
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION + 1);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/' . $lpa->getId() . '/complete');

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

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('not-a-valid-email', (string) $lpa->getPayment()->getEmail());
    }

    public function testConfirmAndPayByCardExistingUnfinishedPaymentRedirectsToPaymentPage(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('existing-ref');

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'state' => ['status' => 'started', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/existing']],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->cardPayments->expects($this->never())->method('recordSuccessfulPayment');

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/existing', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayByCardFinishedUnsuccessfulPaymentCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('finished-ref');

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

        $response = $this->helper->confirmAndPayByCard($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/new', $response->getHeaderLine('location'));
    }

    public function testConfirmAndPayNothingThrowsWhenAmountIsNonZero(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 92;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid option');

        $this->helper->confirmAndPayNothing($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);
    }

    public function testConfirmAndPayNothingWithZeroAmountLocksAndRedirects(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 0;
        $lpa->payment->reducedFeeUniversalCredit = true;

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa, self::IF_MATCH_VERSION);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->helper->confirmAndPayNothing($lpa, $this->createRequest($lpa), self::IF_MATCH_VERSION);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/complete', $response->getHeaderLine('location'));
    }

    private function createRequest(Lpa $lpa, string $backToForm = '', array $routeOptions = []): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($backToForm);
        $flowChecker->method('getRouteOptions')->willReturn($routeOptions);

        return new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->getId() . '/checkout', 'GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);
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

    private function makeGovPayPayment(array $data): GovPayPayment
    {
        return new GovPayPayment((array) json_decode((string) json_encode($data)));
    }
}
