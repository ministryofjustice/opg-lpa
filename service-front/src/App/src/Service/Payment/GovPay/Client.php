<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client for accessing GOV.UK Pay.
 *
 * Before using this client you must have:
 *  - created an account with GOV.UK Pay
 *  - A valid API key.
 */
class Client
{
    public const VERSION = '1.0.0';

    public const PATH_PAYMENT_LIST    = '/v1/payments';
    public const PATH_PAYMENT_CREATE  = '/v1/payments';
    public const PATH_PAYMENT_LOOKUP  = '/v1/payments/%s';
    public const PATH_PAYMENT_EVENTS  = '/v1/payments/%s/events';
    public const PATH_PAYMENT_CANCEL  = '/v1/payments/%s/cancel';
    public const PATH_PAYMENT_REFUND  = '/v1/payments/%s/refunds/%s';
    public const PATH_PAYMENT_REFUNDS = '/v1/payments/%s/refunds';

    private string $baseUrl;
    private string $apiKey;

    public function __construct(private HttpClientInterface $httpClient, ?string $apiKey, string $baseUrl)
    {
        if ($apiKey === null) {
            throw new Exception\InvalidArgumentException("'apiKey' must be set");
        }

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new Exception\InvalidArgumentException(
                "Invalid 'baseUrl' set. This must be set to a valid URL."
            );
        }

        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    // -------------------------------------------------------------------------
    // Public API access methods

    /**
     * Create a new payment.
     *
     * @param int          $amount      Amount in pence.
     * @param string       $reference   Application-side payment reference.
     * @param string       $description Payment description.
     * @param UriInterface $returnUrl   URL the user will be directed back to.
     */
    public function createPayment(
        int $amount,
        string $reference,
        string $description,
        UriInterface $returnUrl,
    ): Response\Payment {
        $response = $this->httpPost(self::PATH_PAYMENT_CREATE, [
            'amount'      => $amount,
            'reference'   => $reference,
            'description' => $description,
            'return_url'  => (string) $returnUrl,
        ]);

        if ($response->getStatusCode() !== 201) {
            throw $this->createErrorException($response);
        }

        return Response\Payment::buildFromResponse($response);
    }

    /**
     * Lookup an existing payment.
     *
     * Returns null if the payment was not found.
     *
     * @param string|Response\Payment $payment GOV.UK payment ID or a Payment response object.
     */
    public function getPayment(string|Response\Payment $payment): ?Response\Payment
    {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;

        $response = $this->httpGet(sprintf(self::PATH_PAYMENT_LOOKUP, $paymentId));

        return $response->getStatusCode() === 200
            ? Response\Payment::buildFromResponse($response)
            : null;
    }

    /**
     * Return all events associated with an existing payment.
     *
     * Returns an empty array if no events were found.
     *
     * @param string|Response\Payment $payment GOV.UK payment ID or a Payment response object.
     * @return array<mixed>|Response\Events
     */
    public function getPaymentEvents(string|Response\Payment $payment): array|Response\Events
    {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;

        $response = $this->httpGet(sprintf(self::PATH_PAYMENT_EVENTS, $paymentId));

        return $response->getStatusCode() === 200
            ? Response\Events::buildFromResponse($response)
            : [];
    }

    /**
     * Return details of all previously requested refunds.
     *
     * @param string|Response\Payment $payment GOV.UK payment ID or a Payment response object.
     * @return array<mixed>|Response\Refunds
     */
    public function getPaymentRefunds(string|Response\Payment $payment): array|Response\Refunds
    {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;

        $response = $this->httpGet(sprintf(self::PATH_PAYMENT_REFUNDS, $paymentId));

        return $response->getStatusCode() === 200
            ? Response\Refunds::buildFromResponse($response)
            : [];
    }

    /**
     * Return details of a single refund. Returns null if not found.
     *
     * @param string|Response\Payment $payment GOV.UK payment ID or a Payment response object.
     * @param string|Response\Refund  $refund  GOV.UK refund ID or a Refund response object.
     */
    public function getPaymentRefund(
        string|Response\Payment $payment,
        string|Response\Refund $refund,
    ): ?Response\Refund {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;
        $refundId  = $refund instanceof Response\Refund   ? $refund->refund_id   : $refund;

        $response = $this->httpGet(sprintf(self::PATH_PAYMENT_REFUND, $paymentId, $refundId));

        return $response->getStatusCode() === 200
            ? Response\Refund::buildFromResponse($response)
            : null;
    }

    /**
     * Cancel an existing payment.
     *
     * @param string|Response\Payment $payment GOV.UK payment ID or a Payment response object.
     */
    public function cancelPayment(string|Response\Payment $payment): bool
    {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;

        $response = $this->httpPost(sprintf(self::PATH_PAYMENT_CANCEL, $paymentId));

        if ($response->getStatusCode() !== 204) {
            throw $this->createErrorException($response);
        }

        return true;
    }

    /**
     * Refund a payment, either in full or in part.
     *
     * @param string|Response\Payment $payment              GOV.UK payment ID or a Payment response object.
     * @param int                     $amount               Amount to refund in pence.
     * @param int|null                $refundAmountAvailable Expected amount available for refund in pence.
     */
    public function refundPayment(
        string|Response\Payment $payment,
        int $amount,
        ?int $refundAmountAvailable = null,
    ): Response\Refund {
        $paymentId = $payment instanceof Response\Payment ? $payment->payment_id : $payment;

        $payload = ['amount' => $amount];

        if ($refundAmountAvailable !== null) {
            $payload['refund_amount_available'] = $refundAmountAvailable;
        }

        $response = $this->httpPost(sprintf(self::PATH_PAYMENT_REFUNDS, $paymentId), $payload);

        if ($response->getStatusCode() !== 202) {
            throw $this->createErrorException($response);
        }

        return Response\Refund::buildFromResponse($response);
    }

    /**
     * Search existing payments.
     *
     * Accepted filter keys: reference, state, from_date, to_date, page, display_size.
     * Date values must be \DateTimeInterface instances.
     *
     * @param array<string, mixed> $filters
     * @return array<mixed>|Response\Payments
     */
    public function searchPayments(array $filters = []): array|Response\Payments
    {
        $filters = array_intersect_key($filters, array_flip([
            'reference',
            'state',
            'from_date',
            'to_date',
            'page',
            'display_size',
        ]));

        foreach (['from_date', 'to_date'] as $dateField) {
            if (isset($filters[$dateField])) {
                if (!($filters[$dateField] instanceof \DateTimeInterface)) {
                    throw new Exception\InvalidArgumentException(
                        "'{$dateField}' must be passed as a PHP DateTime object"
                    );
                }

                $filters[$dateField] = urlencode($filters[$dateField]->format('c'));
            }
        }

        $response = $this->httpGet(self::PATH_PAYMENT_LIST, $filters);

        return $response->getStatusCode() === 200
            ? Response\Payments::buildFromResponse($response)
            : [];
    }

    // -------------------------------------------------------------------------
    // Internal HTTP methods

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'PAY-API-PHP-CLIENT/' . self::VERSION,
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @throws Exception\PayException
     * @throws Exception\ApiException
     */
    private function httpGet(string $path, array $query = []): ResponseInterface
    {
        $url = new Uri($this->baseUrl . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, (string) $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\PayException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 404], true)) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     * @throws Exception\PayException
     * @throws Exception\ApiException
     */
    private function httpPost(string $path, array $payload = []): ResponseInterface
    {
        $url = new Uri($this->baseUrl . $path);

        $body = !empty($payload)
            ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            : null;

        $request = new Request('POST', $url, $this->buildHeaders(), $body);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\PayException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [201, 202, 204], true)) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // Response handling

    private function createErrorException(ResponseInterface $response): Exception\ApiException
    {
        return new Exception\ApiException(
            "HTTP:{$response->getStatusCode()} - Unexpected response from server",
            $response->getStatusCode(),
            $response
        );
    }
}
