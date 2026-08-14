<?php

declare(strict_types=1);

namespace AppTest\Service\Payment;

use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Payment\CardPayments;
use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Exception\PayException as GovPayException;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
use DateTime;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CardPaymentsTest extends TestCase
{
    private const string GATEWAY_REFERENCE = '9aphnjaet2k20e31ue3272m28i';

    private GovPayClient&MockObject $paymentClient;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private LoggerInterface&MockObject $logger;
    private CardPayments $cardPayments;

    protected function setUp(): void
    {
        $this->paymentClient         = $this->createMock(GovPayClient::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->logger                = $this->createMock(LoggerInterface::class);

        $this->cardPayments = new CardPayments(
            $this->paymentClient,
            $this->lpaApplicationService,
            $this->logger,
        );
    }

    private function makeStrandedLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();

        $lpa->payment                   = new Payment();
        $lpa->payment->amount           = 92;
        $lpa->payment->gatewayReference = self::GATEWAY_REFERENCE;

        $lpa->locked      = false;
        $lpa->lockedAt    = null;
        $lpa->completedAt = null;

        return $lpa;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function makeGovPayPayment(array $data): GovPayPayment
    {
        return new GovPayPayment((array) json_decode((string) json_encode($data)));
    }

    private function makeSuccessfulGovPayPayment(string $email = 'payer@example.org'): GovPayPayment
    {
        return $this->makeGovPayPayment([
            'payment_id' => self::GATEWAY_REFERENCE,
            'reference'  => 'A12345678901', // pragma: allowlist secret
            'email'      => $email,
            'state'      => ['status' => 'success', 'finished' => true],
        ]);
    }

    public function testStrandedLpaIsAwaitingConfirmation(): void
    {
        $this->assertTrue($this->cardPayments->isAwaitingConfirmation($this->makeStrandedLpa()));
    }

    public function testLpaWithNoPaymentObjectIsNotAwaitingConfirmation(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->payment = null;

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function emptyGatewayReferenceProvider(): array
    {
        return [
            'null'       => [null],
            'empty'      => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('emptyGatewayReferenceProvider')]
    public function testLpaWithNoUsableGatewayReferenceIsNotAwaitingConfirmation(?string $reference): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->payment->gatewayReference = $reference;

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testLpaWithAPaymentDateIsNotAwaitingConfirmation(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->payment->date = new DateTime();

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testLpaPaidByChequeIsNotAwaitingConfirmation(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testLockedLpaIsNotAwaitingConfirmation(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->locked = true;

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testCompletedLpaIsNotAwaitingConfirmation(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->completedAt = new DateTime();

        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testUnfinishedInstrumentIsNotAwaitingConfirmation(): void
    {
        $lpa = new Lpa();
        $lpa->id       = 91333263035;
        $lpa->document = new Document();
        $lpa->payment  = new Payment();
        $lpa->payment->gatewayReference = self::GATEWAY_REFERENCE;

        $this->assertFalse($lpa->hasFinishedCreation(), 'guard precondition');
        $this->assertFalse($this->cardPayments->isAwaitingConfirmation($lpa));
    }

    public function testNoGovPayCallIsMadeWhenTheLpaIsNotStranded(): void
    {
        $lpa = $this->makeStrandedLpa();
        $lpa->payment->date = new DateTime();

        $this->paymentClient->expects($this->never())->method('getPayment');
        $this->lpaApplicationService->expects($this->never())->method('updateApplication');

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
    }

    public function testSuccessfulPaymentIsRecordedAndReported(): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->expects($this->once())
            ->method('getPayment')
            ->with(self::GATEWAY_REFERENCE)
            ->willReturn($this->makeSuccessfulGovPayPayment());

        $recorded = null;

        $this->lpaApplicationService->expects($this->once())
            ->method('updateApplication')
            ->with(
                $lpa->id,
                $this->callback(function (array $data) use (&$recorded): bool {
                    $recorded = $data['payment'] ?? null;
                    return is_array($recorded);
                })
            )
            ->willReturn($lpa);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('recorded a completed GOV.UK Pay payment'));

        $this->assertTrue($this->cardPayments->recoverCompletedPayment($lpa));

        $this->assertSame(Payment::PAYMENT_TYPE_CARD, $recorded['method']);
        $this->assertSame('A12345678901', $recorded['reference']); // pragma: allowlist secret
        $this->assertNotNull($recorded['date']);
        $this->assertSame(self::GATEWAY_REFERENCE, $recorded['gatewayReference']);

        $this->assertSame(Payment::PAYMENT_TYPE_CARD, $lpa->payment->method);
        $this->assertInstanceOf(DateTime::class, $lpa->payment->date);
    }

    public function testNothingIsRecordedWhenGovPayHasNoRecordOfThePayment(): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->method('getPayment')->willReturn(null);

        $this->lpaApplicationService->expects($this->never())->method('updateApplication');
        $this->logger->expects($this->never())->method('warning');

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
        $this->assertNull($lpa->payment->date);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function unsuccessfulStatusProvider(): array
    {
        return [
            'created'   => ['created', false],
            'started'   => ['started', false],
            'failed'    => ['failed', true],
            'cancelled' => ['cancelled', true],
            'error'     => ['error', true],
        ];
    }

    #[DataProvider('unsuccessfulStatusProvider')]
    public function testNothingIsRecordedWhenThePaymentDidNotSucceed(string $status, bool $finished): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->method('getPayment')->willReturn($this->makeGovPayPayment([
            'payment_id' => self::GATEWAY_REFERENCE,
            'state'      => ['status' => $status, 'finished' => $finished],
        ]));

        $this->lpaApplicationService->expects($this->never())->method('updateApplication');
        $this->logger->expects($this->never())->method('warning');

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
        $this->assertNull($lpa->payment->method);
        $this->assertNull($lpa->payment->date);
    }

    public function testAPaymentWithNoReferenceIsNotRecorded(): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->method('getPayment')->willReturn($this->makeGovPayPayment([
            'payment_id' => self::GATEWAY_REFERENCE,
            'email'      => 'payer@example.org',
            'state'      => ['status' => 'success', 'finished' => true],
        ]));

        $this->lpaApplicationService->expects($this->never())->method('updateApplication');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('carries no reference'));

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
        $this->assertNull($lpa->payment->method);
        $this->assertNull($lpa->payment->date);
    }

    /**
     * @return array<string, array{\Throwable}>
     */
    public static function govPayFailureProvider(): array
    {
        return [
            "pay transport error" => [new GovPayException("GOV.UK Pay unreachable", 503)],
            'unexpected'    => [new RuntimeException('connection reset')],
        ];
    }

    #[DataProvider('govPayFailureProvider')]
    public function testAGovPayFailureLeavesTheLpaUntouched(\Throwable $thrown): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->method('getPayment')->willThrowException($thrown);

        $this->lpaApplicationService->expects($this->never())->method('updateApplication');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Payment recovery'));

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
        $this->assertNull($lpa->payment->date);
    }

    public function testAFailedApiUpdateIsReportedAsCriticalAndDoesNotCompleteCheckout(): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->paymentClient->method('getPayment')->willReturn($this->makeSuccessfulGovPayPayment());
        $this->lpaApplicationService->method('updateApplication')->willReturn(false);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('PAYMENT RECORDING FAILED'));

        $this->logger->expects($this->never())->method('warning');

        $this->assertFalse($this->cardPayments->recoverCompletedPayment($lpa));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function emailNormalisationProvider(): array
    {
        return [
            'mixed case'            => ['Payer@Example.ORG', 'payer@example.org'],
            'surrounding space'     => ['  payer@example.org ', 'payer@example.org'],
        ];
    }

    #[DataProvider('emailNormalisationProvider')]
    public function testGovPayEmailIsNormalisedBeforeItIsStored(string $given, string $expected): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->lpaApplicationService->method('updateApplication')->willReturn($lpa);

        $this->assertTrue($this->cardPayments->recordSuccessfulPayment(
            $lpa,
            $this->makeSuccessfulGovPayPayment($given)
        ));

        $this->assertSame($expected, (string) $lpa->payment->email);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableEmailProvider(): array
    {
        return [
            'empty'      => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('unusableEmailProvider')]
    public function testAnUnusableGovPayEmailIsStoredAsNull(string $given): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->lpaApplicationService->method('updateApplication')->willReturn($lpa);

        $this->cardPayments->recordSuccessfulPayment($lpa, $this->makeSuccessfulGovPayPayment($given));

        $this->assertNull($lpa->payment->email);
    }

    public function testAMissingGovPayEmailIsStoredAsNull(): void
    {
        $lpa = $this->makeStrandedLpa();

        $this->lpaApplicationService->method('updateApplication')->willReturn($lpa);

        $this->cardPayments->recordSuccessfulPayment($lpa, $this->makeGovPayPayment([
            'payment_id' => self::GATEWAY_REFERENCE,
            'reference'  => 'A12345678901', // pragma: allowlist secret
            'state'      => ['status' => 'success', 'finished' => true],
        ]));

        $this->assertNull($lpa->payment->email);
    }
}
