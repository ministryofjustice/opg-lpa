<?php
namespace Alphagov\Pay;

use GuzzleHttp\Psr7\Uri;                            // Concrete PSR-7 URL representation.
use GuzzleHttp\Psr7\Request;                        // Concrete PSR-7 HTTP Request
use Psr\Http\Message\UriInterface;                  // PSR-7 URI Interface
use Psr\Http\Message\ResponseInterface;             // PSR-7 HTTP Response Interface
use Http\Client\HttpClient as HttpClientInterface;  // Interface for a PSR-7 compatible HTTP Client.

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
class Client {

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '0.2.0';

    /**
     * @const string The API endpoint for Pay production.
     */
    const BASE_URL_PRODUCTION = 'https://publicapi.pymnt.uk';

    /**
     * Paths for API endpoints.
     */
    const PATH_PAYMENT_LIST     = '/v1/payments';
    const PATH_PAYMENT_CREATE   = '/v1/payments';
    const PATH_PAYMENT_LOOKUP   = '/v1/payments/%s';
    const PATH_PAYMENT_EVENTS   = '/v1/payments/%s/events';
    const PATH_PAYMENT_CANCEL   = '/v1/payments/%s/cancel';


    /**
     * @var string base scheme and hostname
     */
    protected $baseUrl;

    /**
     * @var HttpClientInterface PSR-7 compatible HTTP Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiKey;
    

    public function __construct( array $config )
    {

        $config = array_merge([
            'httpClient' => null,
            'apiKey' => null,
            'baseUrl' => null,
        ], $config);


        //--------------------------
        // Set base URL

        if( !isset( $config['baseUrl'] ) ){

            // If not set, we default to production
            $this->baseUrl = self::BASE_URL_PRODUCTION;

        } elseif ( filter_var($config['baseUrl'], FILTER_VALIDATE_URL) !== false ) {

            // Else we allow an arbitrary URL to be set.
            $this->baseUrl = $config['baseUrl'];

        } else {

            throw new Exception\InvalidArgumentException(
                "Invalid 'baseUrl' set. This must be either a valid URL, or null to use the production URL."
            );

        }

        //--------------------------
        // Set HTTP Client

        if( $config['httpClient'] instanceof HttpClientInterface ){

            $this->setHttpClient( $config['httpClient'] );

        } else {

            throw new Exception\InvalidArgumentException(
                "An instance of HttpClientInterface must be set under 'httpClient'"
            );

        }

        //--------------------------
        // Set API Key

        if( is_string($config['apiKey']) ){

            $this->apiKey = $config['apiKey'];

        } else {

            throw new Exception\InvalidArgumentException(
                "'apiKey' must be set"
            );

        }

    }

    //------------------------------------------------------------------------------------
    // Public API access methods

    public function createPayment( $amount, $reference, $description, UriInterface $returnUrl ){

        if( !is_int($amount) ){
            throw new Exception\InvalidArgumentException(
                '$amount must be an integer, representing the amount, in pence'
            );
        }

        $response = $this->httpPost( self::PATH_PAYMENT_CREATE, [
            'amount'        => (int)$amount,
            'reference'     => (string)$reference,
            'description'   => (string)$description,
            'return_url'    => (string)$returnUrl,
        ]);

        //---

        if( $response->getStatusCode() != 201 ){
            throw $this->createErrorException( $response );
        }

        return Response\Payment::buildFromResponse($response);
        
    }

    public function getPayment( $payment ){

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf( self::PATH_PAYMENT_LOOKUP, $paymentId );

        $response = $this->httpGet( $path );

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Payment::buildFromResponse($response) : null;

    }

    public function getPaymentEvents( $payment ){

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf( self::PATH_PAYMENT_EVENTS, $paymentId );

        $response = $this->httpGet( $path );

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Events::buildFromResponse($response) : array();

    }

    public function cancelPayment( $payment ){

        $paymentId = ( $payment instanceof Response\Payment ) ? $payment->payment_id : $payment;

        $path = sprintf( self::PATH_PAYMENT_CANCEL, $paymentId );

        $response = $this->httpPost( $path );

        //---

        if( $response->getStatusCode() != 204 ){
            throw $this->createErrorException( $response );
        }

        return true;

    }

    public function searchPayments( array $filters = array() ){

        // Only allow the following filter keys.
        $filters = array_intersect_key( $filters, array_flip([
            'reference',
            'status',
            'from_date',
            'to_date',
        ]));

        $response = $this->httpGet( self::PATH_PAYMENT_LIST, $filters );

        //---

        return ( $response->getStatusCode() == 200 ) ? Response\Payments::buildFromResponse($response) : array();
        
    }

    //------------------------------------------------------------------------------------
    // Internal API access methods


    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders(){

        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'PAY-API-PHP-CLIENT/'.self::VERSION
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
    private function httpGet( $path, array $query = array() ){

        $url = new Uri( $this->baseUrl . $path );

        foreach( $query as $name => $value ){
            $url = URI::withQueryValue($url, $name, $value );
        }

        //---

        $request = new Request(
            'GET',
            $url,
            $this->buildHeaders()
        );

        try {

            $response = $this->getHttpClient()->sendRequest( $request );

        } catch (\RuntimeException $e){
            throw new Exception\PayException( $e->getMessage(), $e->getCode(), $e );
        }

        //---

        if( !in_array($response->getStatusCode(), [200, 404]) ){
            throw $this->createErrorException( $response );
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
    private function httpPost( $path, array $payload = array() ){

        $url = new Uri( $this->baseUrl . $path );

        $request = new Request(
            'POST',
            $url,
            $this->buildHeaders(),
            ( !empty($payload) ) ? json_encode($payload) : null
        );

        try {

            $response = $this->getHttpClient()->sendRequest( $request );

        } catch (\RuntimeException $e){
            throw new Exception\PayException( $e->getMessage(), $e->getCode(), $e );
        }

        //---

        if( !in_array($response->getStatusCode(), [201, 204]) ){
            throw $this->createErrorException( $response );
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
    protected function createErrorException( ResponseInterface $response ){

        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ApiException( $message, $response->getStatusCode(), $response );

    }

    //------------------------------------------------------------------------------------
    // Getters and setters

    /**
     * @return HttpClientInterface
     * @throws Exception\UnexpectedValueException
     */
    final protected function getHttpClient(){

        if( !( $this->httpClient instanceof HttpClientInterface ) ){
            throw new Exception\UnexpectedValueException('Invalid HttpClient set');
        }

        return $this->httpClient;

    }

    /**
     * @param HttpClientInterface $client
     */
    final protected function setHttpClient( HttpClientInterface $client ){

        $this->httpClient = $client;

    }


}
