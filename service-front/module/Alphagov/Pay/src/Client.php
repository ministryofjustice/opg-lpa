<?php

namespace Alphagov\Pay;

use GuzzleHttp\Psr7\Uri;                            // Concrete PSR-7 URL representation.
use GuzzleHttp\Psr7\Request;                        // Concrete PSR-7 HTTP Request
use Psr\Http\Message\UriInterface;                  // PSR-7 URI Interface
use Psr\Http\Message\ResponseInterface;             // PSR-7 HTTP Response Interface
use Http\Client\HttpClient as HttpClientInterface;

// Interface for a PSR-7 compatible HTTP Client.

/**
 * Client for accessing GOV.UK Pay.
 *
 * Before using this client you must have:
 *  - created an account with GOV.UK Pay
 *  - A valid API key.
 *
 * Class Client
 * @package Alphagov\Pay
 */
class Client
{
    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    public const VERSION = '1.0.0';

    /**
     * @const string The API endpoint for Pay production.
     */
    public const BASE_URL_PRODUCTION = 'https://publicapi.payments.service.gov.uk';

    /**
     * Paths for API endpoints.
     */
    public const PATH_PAYMENT_LIST     = '/v1/payments';
    public const PATH_PAYMENT_CREATE   = '/v1/payments';
    public const PATH_PAYMENT_LOOKUP   = '/v1/payments/%s';
    public const PATH_PAYMENT_EVENTS   = '/v1/payments/%s/events';
    public const PATH_PAYMENT_CANCEL   = '/v1/payments/%s/cancel';
    public const PATH_PAYMENT_REFUND   = '/v1/payments/%s/refunds/%s';
    public const PATH_PAYMENT_REFUNDS  = '/v1/payments/%s/refunds';


    /**
     * @var string base scheme and hostname
     */
    protected $baseUrl;

    /**
     * @var HttpClientInterface PSR-7 compatible HTTP Client
     */
    private $httpClient;

    /**
     * @var string API key
     */
    private $apiKey;


    /**
     * Instantiates a new GOV.UK Pay client.
     *
     * The client constructor accepts the following options:
     *  - httpClient: (HttpClientInterface)
     *      Required.
     *  - apiKey: (string)
     *      Required.
     *  - baseUrl: (string)
     *      Optional. The base URL to make API calls to.
     *      If not set, this defaults to the production API.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {

        $config = array_merge([
            'httpClient' => null,
            'apiKey' => null,
            'baseUrl' => null,
        ], $config);


        //--------------------------
        // Set base URL

        if (!isset($config['baseUrl'])) {
            // If not set, we default to production
            $this->baseUrl = self::BASE_URL_PRODUCTION;
        } elseif (filter_var($config['baseUrl'], FILTER_VALIDATE_URL) !== false) {
            // Else we allow an arbitrary URL to be set.
            $this->baseUrl = $config['baseUrl'];
        } else {
            throw new Exception\InvalidArgumentException(
                "Invalid 'baseUrl' set. This must be either a valid URL, or null to use the production URL."
            );
        }

        //--------------------------
        // Set HTTP Client

        if ($config['httpClient'] instanceof HttpClientInterface) {
            $this->setHttpClient($config['httpClient']);
        } else {
            throw new Exception\InvalidArgumentException(
                "An instance of HttpClientInterface must be set under 'httpClient'"
            );
        }

        //--------------------------
        // Set API Key

        if (is_string($config['apiKey'])) {
            $this->apiKey = $config['apiKey'];
        } else {
            throw new Exception\InvalidArgumentException(
                "'apiKey' must be set"
            );
        }
    }

    //------------------------------------------------------------------------------------
    // Public API access methods

    /**
     * Create a new payment.
     *
     * @param $amount int amount, in pence
     * @param $reference string Application side payment reference
     * @param $description string Payment description
     * @param $returnUrl UriInterface URL the user will be directed back to.
     * @return Response\Payment
     */
    public function createPayment($amount, $reference, $description, UriInterface $returnUrl)
    {

        if (!is_int($amount)) {
            throw new Exception\InvalidArgumentException(
                '$amount must be an integer, representing the amount, in pence'
            );
        }

        $response = $this->httpPost(self::PATH_PAYMENT_CREATE, [
            'amount'        => (int)$amount,
            'reference'     => (string)$reference,
            'description'   => (string)$description,
            'return_url'    => (string)$returnUrl,
        ]);

        //---

        if ($response->getStatusCode() != 201) {
            throw $this->createErrorException($response);
        }

        return Response\Payment::buildFromResponse($response);
    }

    /**
     * Lookup an existing payment.
     *
     * Returns a payment Payment Response object.
     * If the payment was not found, NULL is returned.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @return null|Response\Payment
     */
    public function getPayment($payment)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf(self::PATH_PAYMENT_LOOKUP, $paymentId);

        $response = $this->httpGet($path);

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Payment::buildFromResponse($response) : null;
    }

    /**
     * Return all events associated with an existing payment.
     *
     * Returns a Events Response object.
     * If no events were found, an empty array is returned.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @return array|Response\Events
     */
    public function getPaymentEvents($payment)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf(self::PATH_PAYMENT_EVENTS, $paymentId);

        $response = $this->httpGet($path);

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Events::buildFromResponse($response) : [];
    }

    /**
     * Return details of all previously requested refunds.
     *
     * Returns a Refunds Response object.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @return array|Response\Refunds
     */
    public function getPaymentRefunds($payment)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf(self::PATH_PAYMENT_REFUNDS, $paymentId);

        $response = $this->httpGet($path);

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Refunds::buildFromResponse($response) : [];
    }

    /**
     * Return details of a single refund.
     *
     * Returns a Refund Response object.
     * If the refund was not found, NULL is returned.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @param $refund string|Response\Refund GOV.UK refund ID or a Refund Response object.
     * @return null|Response\Refund
     */
    public function getPaymentRefund($payment, $refund)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;
        $refundId  = ( $refund instanceof Response\Refund   ) ? $refund->refund_id   : $refund;

        $path = sprintf(self::PATH_PAYMENT_REFUND, $paymentId, $refundId);

        $response = $this->httpGet($path);

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Refund::buildFromResponse($response) : null;
    }

    /**
     * Cancels an existing payment.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @return bool
     */
    public function cancelPayment($payment)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf(self::PATH_PAYMENT_CANCEL, $paymentId);

        $response = $this->httpPost($path);

        //---

        if ($response->getStatusCode() != 204) {
            throw $this->createErrorException($response);
        }

        return true;
    }

    /**
     * Refunds a payment, either in full or in part.
     *
     * @param $payment string|Response\Payment GOV.UK payment ID or a Payment Response object.
     * @param $amount int The amount to refund, in pence.
     * @param $refundAmountAvailable null|int The expected amount available for refund, in pence.
     * @return Response\Refund
     */
    public function refundPayment($payment, $amount, $refundAmountAvailable = null)
    {

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf(self::PATH_PAYMENT_REFUNDS, $paymentId);

        //---

        $payload = [
            'amount' => (int)$amount,
        ];

        if (is_numeric($refundAmountAvailable)) {
            $payload['refund_amount_available'] = (int)$refundAmountAvailable;
        }

        //---

        $response = $this->httpPost($path, $payload);

        //---

        if ($response->getStatusCode() != 202) {
            throw $this->createErrorException($response);
        }

        return Response\Refund::buildFromResponse($response);
    }

    /**
     * Searches existing transactions.
     *
     * @param $filters array key=>value filters applied to the search.
     * @return array|Response\Payments
     */
    public function searchPayments(array $filters = [])
    {

        // Only allow the following filter keys.
        $filters = array_intersect_key($filters, array_flip([
            'reference',
            'state',
            'from_date',
            'to_date',
            'page',
            'display_size',
        ]));

        //---

        // We force the use of PHP's DateTime to ensure dates are encoded correctly.
        foreach ([ 'from_date', 'to_date' ] as $dateField) {
            if (isset($filters[$dateField])) {
                // If the date is not a DateTime, then we don't allow it.
                if (!($filters[$dateField] instanceof \DateTime)) {
                    throw new Exception\InvalidArgumentException("'{$dateField}' must be passed as a PHP DateTime object");
                }

                // Otherwise convert it to a URL encoded ISO 8601 string.
                $filters[$dateField] = urlencode($filters[$dateField]->format('c'));
            } // if
        } // foreach

        //---

        $response = $this->httpGet(self::PATH_PAYMENT_LIST, $filters);

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Payments::buildFromResponse($response) : [];
    }

    //------------------------------------------------------------------------------------
    // Internal API access methods


    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {

        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'PAY-API-PHP-CLIENT/' . self::VERSION
        ];
    }

    //-------------------------------------------
    // GET & POST requests

    /**
     * Performs a GET against the Pay API.
     *
     * @param string $path
     * @param array  $query
     *
     * @return ResponseInterface
     * @throw Exception\PayException | Exception\ApiException | Exception\UnexpectedValueException
     */
    private function httpGet($path, array $query = [])
    {

        $url = new Uri($this->baseUrl . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        //---

        $request = new Request(
            'GET',
            $url,
            $this->buildHeaders()
        );

        try {
            $response = $this->getHttpClient()->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\PayException($e->getMessage(), $e->getCode(), $e);
        }

        //---

        if (!in_array($response->getStatusCode(), [200, 404])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }


    /**
     * Performs a POST against the Pay API.
     *
     * @param string $path
     * @param array  $payload
     *
     * @return ResponseInterface
     * @throw Exception\PayException | Exception\ApiException | Exception\UnexpectedValueException
     */
    private function httpPost($path, array $payload = [])
    {

        $url = new Uri($this->baseUrl . $path);

        $request = new Request(
            'POST',
            $url,
            $this->buildHeaders(),
            ( !empty($payload) ) ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null
        );

        try {
            $response = $this->getHttpClient()->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\PayException($e->getMessage(), $e->getCode(), $e);
        }

        //---

        if (!in_array($response->getStatusCode(), [201, 202, 204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    //-------------------------------------------
    // Response Handling

    /**
     * Called with a response from the API when the response code was unsuccessful. i.e. not 20X.
     *
     * @param ResponseInterface $response
     *
     * @return Exception\ApiException
     */
    protected function createErrorException(ResponseInterface $response)
    {

        $message = "HTTP:{$response->getStatusCode()} - Unexpected response from server";

        return new Exception\ApiException($message, $response->getStatusCode(), $response);
    }

    //------------------------------------------------------------------------------------
    // Getters and setters

    /**
     * @return HttpClientInterface
     * @throws Exception\UnexpectedValueException
     */
    final protected function getHttpClient()
    {

        if (!( $this->httpClient instanceof HttpClientInterface )) {
            throw new Exception\UnexpectedValueException('Invalid HttpClient set');
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $client
     */
    final protected function setHttpClient(HttpClientInterface $client)
    {

        $this->httpClient = $client;
    }
}
