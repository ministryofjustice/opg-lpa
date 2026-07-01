<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception\ApiException;
use App\Service\Payment\GovPay\Response\AbstractData;
use App\Service\Payment\GovPay\Response\IncludeResponseTrait;
use App\Service\Payment\GovPay\Response\Payment;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

#[CoversClass(Payment::class)]
#[CoversClass(AbstractData::class)]
#[CoversClass(IncludeResponseTrait::class)]
class PaymentTest extends TestCase
{
    /**
     * @param array<mixed> $data
     */
    private function makePayment(array $data): Payment
    {
        // Build via buildFromResponse to exercise the full chain
        $json     = (string) json_encode($data);
        $response = new GuzzleResponse(200, [], $json);

        return Payment::buildFromResponse($response);
    }

    private function paymentWithStatus(string $status, bool $finished): Payment
    {
        return $this->makePayment([
            'payment_id' => 'test-id',
            'amount'     => 8200,
            'state'      => ['status' => $status, 'finished' => $finished],
            '_links'     => ['next_url' => ['href' => 'https://pay.gov.uk/pay/test-id']],
        ]);
    }

    // -------------------------------------------------------------------------
    // isFinished

    public function testIsFinishedReturnsTrueWhenFinished(): void
    {
        $this->assertTrue($this->paymentWithStatus('success', true)->isFinished());
    }

    public function testIsFinishedReturnsFalseWhenNotFinished(): void
    {
        $this->assertFalse($this->paymentWithStatus('started', false)->isFinished());
    }

    // -------------------------------------------------------------------------
    // Status methods

    public function testIsCreated(): void
    {
        $this->assertTrue($this->paymentWithStatus('created', false)->isCreated());
        $this->assertFalse($this->paymentWithStatus('started', false)->isCreated());
    }

    public function testIsStarted(): void
    {
        $this->assertTrue($this->paymentWithStatus('started', false)->isStarted());
        $this->assertFalse($this->paymentWithStatus('created', false)->isStarted());
    }

    public function testIsSubmitted(): void
    {
        $this->assertTrue($this->paymentWithStatus('submitted', false)->isSubmitted());
        $this->assertFalse($this->paymentWithStatus('created', false)->isSubmitted());
    }

    public function testIsSuccess(): void
    {
        $this->assertTrue($this->paymentWithStatus('success', true)->isSuccess());
        $this->assertFalse($this->paymentWithStatus('failed', true)->isSuccess());
    }

    public function testIsFailed(): void
    {
        $this->assertTrue($this->paymentWithStatus('failed', true)->isFailed());
        $this->assertFalse($this->paymentWithStatus('success', true)->isFailed());
    }

    public function testIsCancelled(): void
    {
        $this->assertTrue($this->paymentWithStatus('cancelled', true)->isCancelled());
        $this->assertFalse($this->paymentWithStatus('success', true)->isCancelled());
    }

    public function testIsError(): void
    {
        $this->assertTrue($this->paymentWithStatus('error', true)->isError());
        $this->assertFalse($this->paymentWithStatus('success', true)->isError());
    }

    // -------------------------------------------------------------------------
    // getPaymentPageUrl

    public function testGetPaymentPageUrlReturnsUriWhenPaymentIsNotFinished(): void
    {
        $payment = $this->makePayment([
            'payment_id' => 'id',
            'state'      => ['status' => 'started', 'finished' => false],
            '_links'     => ['next_url' => ['href' => 'https://pay.gov.uk/pay/id']],
        ]);

        $url = $payment->getPaymentPageUrl();

        $this->assertInstanceOf(UriInterface::class, $url);
        $this->assertSame('https://pay.gov.uk/pay/id', (string) $url);
    }

    public function testGetPaymentPageUrlReturnsNullWhenPaymentIsFinished(): void
    {
        $payment = $this->paymentWithStatus('success', true);
        $this->assertNull($payment->getPaymentPageUrl());
    }

    public function testGetPaymentPageUrlReturnsNullWhenNextUrlLinkIsMissing(): void
    {
        $payment = $this->makePayment([
            'payment_id' => 'id',
            'state'      => ['status' => 'created', 'finished' => false],
            '_links'     => [],
        ]);

        $this->assertNull($payment->getPaymentPageUrl());
    }

    // -------------------------------------------------------------------------
    // buildFromResponse

    public function testBuildFromResponseStoresResponseObject(): void
    {
        $guzzleResponse = new GuzzleResponse(200, [], json_encode([
            'payment_id' => 'id',
            'state'      => ['status' => 'created', 'finished' => false],
            '_links'     => [],
        ]));

        $payment = Payment::buildFromResponse($guzzleResponse);

        $this->assertSame($guzzleResponse, $payment->getResponse());
    }

    public function testBuildFromResponseThrowsOnMalformedJson(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Malformed JSON response from server');

        Payment::buildFromResponse(new GuzzleResponse(200, [], 'not-json'));
    }

    // -------------------------------------------------------------------------
    // Status constants

    public function testStatusConstantsHaveExpectedValues(): void
    {
        $this->assertSame('created', Payment::STATUS_CREATED);
        $this->assertSame('started', Payment::STATUS_STARTED);
        $this->assertSame('submitted', Payment::STATUS_SUBMITTED);
        $this->assertSame('success', Payment::STATUS_SUCCESS);
        $this->assertSame('failed', Payment::STATUS_FAILED);
        $this->assertSame('cancelled', Payment::STATUS_CANCELLED);
        $this->assertSame('error', Payment::STATUS_ERROR);
    }
}
