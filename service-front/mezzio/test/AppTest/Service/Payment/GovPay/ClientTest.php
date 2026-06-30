<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\GovPay;

use App\Service\Payment\GovPay\Client;
use App\Service\Payment\GovPay\Exception\ApiException;
use App\Service\Payment\GovPay\Exception\InvalidArgumentException;
use App\Service\Payment\GovPay\Exception\PayException;
use App\Service\Payment\GovPay\Response\Events;
use App\Service\Payment\GovPay\Response\Payment;
use App\Service\Payment\GovPay\Response\Payments;
use App\Service\Payment\GovPay\Response\Refund;
use App\Service\Payment\GovPay\Response\Refunds;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient as HttpClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    // -------------------------------------------------------------------------
    // Helpers

    private function makeClient(array $overrides = []): Client
    {
        return new Client(array_merge([
            'httpClient' => $this->httpClient,
            'apiKey'     => 'test-api-key', // pragma: allowlist secret
            'baseUrl'    => 'https://pay.example.com',
        ], $overrides));
    }

    /**
     * Build a real Guzzle response so getBody() returns a proper Stream whose
     * __toString() yields valid JSON consumed by IncludeResponseTrait.
     *
     * @param array<mixed> $body
     */
    private function makeHttpResponse(int $status, array $body): GuzzleResponse
    {
        return new GuzzleResponse($status, [], (string) json_encode($body));
    }

    private function makePaymentData(string $id, string $status, bool $finished): array
    {
        return [
            'payment_id' => $id,
            'reference'  => 'ref-' . $id,
            'email'      => 'test@example.com',
            'amount'     => 8200,
            'state'      => ['status' => $status, 'finished' => $finished],
            '_links'     => ['next_url' => ['href' => 'https://pay.gov.uk/pay/' . $id]],
        ];
    }

    // -------------------------------------------------------------------------
    // Constructor

    public function testConstructorDefaultsToProductionUrl(): void
    {
        $client = new Client([
            'httpClient' => $this->httpClient,
            'apiKey'     => 'key', // pragma: allowlist secret
        ]);

        // White-box: access protected $baseUrl via reflection
        $ref = new \ReflectionProperty(Client::class, 'baseUrl');
        $this->assertSame(Client::BASE_URL_PRODUCTION, $ref->getValue($client));
    }

    public function testConstructorAcceptsCustomBaseUrl(): void
    {
        $client = $this->makeClient(['baseUrl' => 'https://custom.example.com']);

        $ref = new \ReflectionProperty(Client::class, 'baseUrl');
        $this->assertSame('https://custom.example.com', $ref->getValue($client));
    }

    public function testConstructorThrowsOnInvalidBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'baseUrl'");

        $this->makeClient(['baseUrl' => 'not-a-url']);
    }

    public function testConstructorThrowsWhenHttpClientMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HttpClientInterface');

        new Client(['apiKey' => 'key', 'httpClient' => null]); // pragma: allowlist secret
    }

    public function testConstructorThrowsWhenHttpClientInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client(['apiKey' => 'key', 'httpClient' => new \stdClass()]); // pragma: allowlist secret
    }

    public function testConstructorThrowsWhenApiKeyMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'apiKey' must be set");

        new Client(['httpClient' => $this->httpClient, 'apiKey' => null]);
    }

    // -------------------------------------------------------------------------
    // createPayment

    public function testCreatePaymentReturnsPaymentOn201(): void
    {
        $data     = $this->makePaymentData('pay-id-1', 'created', false);
        $response = $this->makeHttpResponse(201, $data);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $client  = $this->makeClient();
        $payment = $client->createPayment(8200, 'ref-001', 'Test LPA', new Uri('https://return.example.com'));

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('pay-id-1', $payment->payment_id);
    }

    public function testCreatePaymentThrowsOnNon201(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(400, ['message' => 'Bad request']));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:400');

        $this->makeClient()->createPayment(8200, 'ref', 'Test', new Uri('https://return.example.com'));
    }

    public function testCreatePaymentSendsCorrectPayload(): void
    {
        $response = $this->makeHttpResponse(201, $this->makePaymentData('id', 'created', false));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = (array) json_decode((string) $request->getBody(), true);
                return $body['amount'] === 8200
                    && $body['reference'] === 'MY-REF'
                    && $body['description'] === 'Test LPA'
                    && $body['return_url'] === 'https://return.example.com';
            }))
            ->willReturn($response);

        $this->makeClient()->createPayment(8200, 'MY-REF', 'Test LPA', new Uri('https://return.example.com'));
    }

    // -------------------------------------------------------------------------
    // getPayment

    public function testGetPaymentReturnsPaymentOn200(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, $this->makePaymentData('pay-id-2', 'success', true)));

        $payment = $this->makeClient()->getPayment('pay-id-2');

        $this->assertInstanceOf(Payment::class, $payment);
    }

    public function testGetPaymentReturnsNullOn404(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->assertNull($this->makeClient()->getPayment('missing-id'));
    }

    public function testGetPaymentAcceptsPaymentObject(): void
    {
        $existingPayment = new Payment($this->makePaymentData('obj-id', 'created', false));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(
                fn (RequestInterface $req): bool => str_contains((string) $req->getUri(), 'obj-id')
            ))
            ->willReturn($this->makeHttpResponse(200, $this->makePaymentData('obj-id', 'success', true)));

        $this->makeClient()->getPayment($existingPayment);
    }

    // -------------------------------------------------------------------------
    // getPaymentEvents

    public function testGetPaymentEventsReturnsEventsOn200(): void
    {
        $body = [
            'payment_id' => 'pay-1',
            'events'     => [
                ['payment_id' => 'pay-1', 'updated' => '2024-01-01', 'state' => []],
            ],
        ];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, $body));

        $result = $this->makeClient()->getPaymentEvents('pay-1');

        $this->assertInstanceOf(Events::class, $result);
    }

    public function testGetPaymentEventsReturnsEmptyArrayOnNon200(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->assertSame([], $this->makeClient()->getPaymentEvents('missing'));
    }

    // -------------------------------------------------------------------------
    // getPaymentRefunds

    public function testGetPaymentRefundsReturnsRefundsOn200(): void
    {
        $body = [
            'payment_id' => 'pay-1',
            '_embedded'  => (object) [
                'refunds' => [
                    ['refund_id' => 'ref-1', 'amount' => 1000, 'status' => 'success'],
                ],
            ],
        ];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, (array) json_decode((string) json_encode($body))));

        $result = $this->makeClient()->getPaymentRefunds('pay-1');

        $this->assertInstanceOf(Refunds::class, $result);
    }

    public function testGetPaymentRefundsReturnsEmptyArrayOnNon200(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->assertSame([], $this->makeClient()->getPaymentRefunds('missing'));
    }

    // -------------------------------------------------------------------------
    // getPaymentRefund

    public function testGetPaymentRefundReturnsRefundOn200(): void
    {
        $body = ['refund_id' => 'ref-id', 'amount' => 1000, 'status' => 'success'];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, $body));

        $result = $this->makeClient()->getPaymentRefund('pay-id', 'ref-id');

        $this->assertInstanceOf(Refund::class, $result);
    }

    public function testGetPaymentRefundReturnsNullOn404(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->assertNull($this->makeClient()->getPaymentRefund('pay-id', 'ref-id'));
    }

    public function testGetPaymentRefundAcceptsResponseObjects(): void
    {
        $payment = new Payment($this->makePaymentData('pay-obj', 'success', true));
        $refund  = new Refund(['refund_id' => 'ref-obj', 'amount' => 500, 'status' => 'success']);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(
                fn (RequestInterface $req): bool =>
                    str_contains((string) $req->getUri(), 'pay-obj') &&
                    str_contains((string) $req->getUri(), 'ref-obj')
            ))
            ->willReturn($this->makeHttpResponse(200, ['refund_id' => 'ref-obj', 'amount' => 500, 'status' => 'success']));

        $this->makeClient()->getPaymentRefund($payment, $refund);
    }

    // -------------------------------------------------------------------------
    // cancelPayment

    public function testCancelPaymentReturnsTrueOn204(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(204, [], ''));

        $this->assertTrue($this->makeClient()->cancelPayment('pay-id'));
    }

    public function testCancelPaymentThrowsOnNon204(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(422, ['message' => 'Unprocessable']));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP:422');

        $this->makeClient()->cancelPayment('pay-id');
    }

    // -------------------------------------------------------------------------
    // refundPayment

    public function testRefundPaymentReturnsRefundOn202(): void
    {
        $body = ['refund_id' => 'ref-new', 'amount' => 500, 'status' => 'submitted'];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(202, $body));

        $result = $this->makeClient()->refundPayment('pay-id', 500);

        $this->assertInstanceOf(Refund::class, $result);
    }

    public function testRefundPaymentIncludesRefundAmountAvailableWhenProvided(): void
    {
        $response = $this->makeHttpResponse(202, ['refund_id' => 'r', 'amount' => 500, 'status' => 'submitted']);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = (array) json_decode((string) $request->getBody(), true);
                return isset($body['refund_amount_available']) && $body['refund_amount_available'] === 1000;
            }))
            ->willReturn($response);

        $this->makeClient()->refundPayment('pay-id', 500, 1000);
    }

    public function testRefundPaymentOmitsRefundAmountAvailableWhenNull(): void
    {
        $response = $this->makeHttpResponse(202, ['refund_id' => 'r', 'amount' => 500, 'status' => 'submitted']);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = (array) json_decode((string) $request->getBody(), true);
                return !isset($body['refund_amount_available']);
            }))
            ->willReturn($response);

        $this->makeClient()->refundPayment('pay-id', 500);
    }

    public function testRefundPaymentThrowsOnNon202(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(422, ['message' => 'Cannot refund']));

        $this->expectException(ApiException::class);

        $this->makeClient()->refundPayment('pay-id', 500);
    }

    // -------------------------------------------------------------------------
    // searchPayments

    public function testSearchPaymentsReturnsPaymentsOn200(): void
    {
        $body = [
            'results' => [
                $this->makePaymentData('p1', 'success', true),
            ],
        ];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, $body));

        $result = $this->makeClient()->searchPayments(['reference' => 'ref-001']);

        $this->assertInstanceOf(Payments::class, $result);
    }

    public function testSearchPaymentsReturnsEmptyArrayOnNon200(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->assertSame([], $this->makeClient()->searchPayments());
    }

    public function testSearchPaymentsStripsUnknownFilterKeys(): void
    {
        $body = ['results' => []];

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $query = (string) $request->getUri()->getQuery();
                return str_contains($query, 'reference') && !str_contains($query, 'unknown_key');
            }))
            ->willReturn($this->makeHttpResponse(200, $body));

        $this->makeClient()->searchPayments(['reference' => 'ref-001', 'unknown_key' => 'ignored']);
    }

    public function testSearchPaymentsConvertsDateTimeToIso8601(): void
    {
        $date = new \DateTimeImmutable('2024-06-01T12:00:00+00:00');
        $body = ['results' => []];

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($date): bool {
                $query = urldecode((string) $request->getUri()->getQuery());
                return str_contains($query, $date->format('c'));
            }))
            ->willReturn($this->makeHttpResponse(200, $body));

        $this->makeClient()->searchPayments(['from_date' => $date]);
    }

    public function testSearchPaymentsAcceptsDateTimeInstance(): void
    {
        $date = new \DateTime('2024-06-01T12:00:00+00:00');
        $body = ['results' => []];

        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(200, $body));

        // Should not throw
        $this->makeClient()->searchPayments(['from_date' => $date]);
        $this->assertTrue(true);
    }

    public function testSearchPaymentsThrowsWhenFromDateIsNotDateTimeInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'from_date' must be passed as a PHP DateTime object");

        $this->makeClient()->searchPayments(['from_date' => '2024-06-01']);
    }

    public function testSearchPaymentsThrowsWhenToDateIsNotDateTimeInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'to_date' must be passed as a PHP DateTime object");

        $this->makeClient()->searchPayments(['to_date' => '2024-06-01']);
    }

    // -------------------------------------------------------------------------
    // Authorization header

    public function testRequestIncludesBearerAuthorizationHeader(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getHeaderLine('Authorization') === 'Bearer test-api-key';
            }))
            ->willReturn(new GuzzleResponse(404, [], '{}'));

        $this->makeClient()->getPayment('some-id');
    }

    // -------------------------------------------------------------------------
    // RuntimeException wrapping

    public function testHttpClientRuntimeExceptionIsWrappedAsPayException(): void
    {
        $this->httpClient->method('sendRequest')
            ->willThrowException(new RuntimeException('Connection refused', 0));

        $this->expectException(PayException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->makeClient()->getPayment('pay-id');
    }

    public function testHttpClientRuntimeExceptionWrappedOnPost(): void
    {
        $this->httpClient->method('sendRequest')
            ->willThrowException(new RuntimeException('Timeout', 0));

        $this->expectException(PayException::class);
        $this->expectExceptionMessage('Timeout');

        $this->makeClient()->createPayment(100, 'ref', 'desc', new Uri('https://return.example.com'));
    }

    // -------------------------------------------------------------------------
    // Error exception structure

    public function testApiExceptionContainsOriginalResponse(): void
    {
        $this->httpClient->method('sendRequest')
            ->willReturn($this->makeHttpResponse(500, ['message' => 'Internal error']));

        try {
            $this->makeClient()->getPayment('pay-id');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertSame(500, $e->getApiResponse()->getStatusCode());
        }
    }
}
