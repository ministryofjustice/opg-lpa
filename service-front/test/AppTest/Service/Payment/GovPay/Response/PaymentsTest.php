<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception\UnexpectedValueException;
use App\Service\Payment\GovPay\Response\Payment;
use App\Service\Payment\GovPay\Response\Payments;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Payments::class)]
class PaymentsTest extends TestCase
{
    private function makePaymentData(string $id): array
    {
        return [
            'payment_id' => $id,
            'amount'     => 8200,
            'state'      => ['status' => 'success', 'finished' => true],
            '_links'     => [],
        ];
    }

    public function testConstructorMapsResultsToPaymentObjects(): void
    {
        $payments = new Payments([
            'results' => [
                $this->makePaymentData('p1'),
                $this->makePaymentData('p2'),
            ],
        ]);

        $this->assertCount(2, $payments);

        foreach ($payments as $payment) {
            $this->assertInstanceOf(Payment::class, $payment);
        }
    }

    public function testConstructorThrowsWhenResultsKeyMissing(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Payments response missing 'results' key");

        new Payments([]);
    }

    public function testConstructorThrowsWhenResultsIsNotAnArray(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new Payments(['results' => 'not-an-array']);
    }

    public function testConstructorHandlesEmptyResultsArray(): void
    {
        $payments = new Payments(['results' => []]);
        $this->assertCount(0, $payments);
    }

    public function testBuildFromResponseProducesPaymentsInstance(): void
    {
        $body = json_encode([
            'results' => [
                $this->makePaymentData('p1'),
            ],
        ]);

        $payments = Payments::buildFromResponse(new GuzzleResponse(200, [], $body));

        $this->assertInstanceOf(Payments::class, $payments);
        $this->assertCount(1, $payments);
    }
}
