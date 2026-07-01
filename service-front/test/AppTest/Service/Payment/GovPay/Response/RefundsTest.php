<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception\UnexpectedValueException;
use App\Service\Payment\GovPay\Response\Refund;
use App\Service\Payment\GovPay\Response\Refunds;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Refunds::class)]
class RefundsTest extends TestCase
{
    private function makeRefundData(string $id = 'ref-1'): array
    {
        return ['refund_id' => $id, 'amount' => 1000, 'status' => 'success'];
    }

    private function makeRefundsBody(string $paymentId = 'pay-1', array $refunds = []): array
    {
        return [
            'payment_id' => $paymentId,
            '_embedded'  => (object) ['refunds' => $refunds],
        ];
    }

    public function testConstructorMapsRefundsToRefundObjects(): void
    {
        $data = $this->makeRefundsBody('pay-1', [
            $this->makeRefundData('ref-1'),
            $this->makeRefundData('ref-2'),
        ]);

        // Simulate json_decode round-trip (as the real client does)
        $decoded = (array) json_decode((string) json_encode($data));

        $refunds = new Refunds($decoded);

        $this->assertCount(2, $refunds);

        foreach ($refunds as $refund) {
            $this->assertInstanceOf(Refund::class, $refund);
        }
    }

    public function testConstructorSetsPaymentId(): void
    {
        $data    = $this->makeRefundsBody('pay-xyz', [$this->makeRefundData()]);
        $decoded = (array) json_decode((string) json_encode($data));

        $refunds = new Refunds($decoded);

        $this->assertSame('pay-xyz', $refunds->payment_id);
    }

    public function testConstructorDefaultsPaymentIdToEmptyString(): void
    {
        $data = [
            '_embedded' => (object) ['refunds' => []],
        ];
        $decoded = (array) json_decode((string) json_encode($data));

        $refunds = new Refunds($decoded);

        $this->assertSame('', $refunds->payment_id);
    }

    public function testConstructorThrowsWhenEmbeddedRefundsMissing(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Refunds response missing '_embedded->refunds' key");

        new Refunds(['payment_id' => 'pay-1']);
    }

    public function testConstructorThrowsWhenEmbeddedRefundsIsNotAnArray(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new Refunds([
            'payment_id' => 'pay-1',
            '_embedded'  => (object) ['refunds' => 'not-an-array'],
        ]);
    }

    public function testConstructorHandlesEmptyRefundsArray(): void
    {
        $data    = $this->makeRefundsBody('pay-1', []);
        $decoded = (array) json_decode((string) json_encode($data));

        $refunds = new Refunds($decoded);

        $this->assertCount(0, $refunds);
    }

    public function testBuildFromResponseProducesRefundsInstance(): void
    {
        $body = json_encode([
            'payment_id' => 'pay-1',
            '_embedded'  => ['refunds' => [$this->makeRefundData()]],
        ]);

        $refunds = Refunds::buildFromResponse(new GuzzleResponse(200, [], $body));

        $this->assertInstanceOf(Refunds::class, $refunds);
        $this->assertCount(1, $refunds);
    }
}
