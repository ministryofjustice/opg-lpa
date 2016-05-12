<?php
namespace MinistryOfJustice\PostcodeInfo;

use GuzzleHttp\Psr7\Uri;                            // Concrete PSR-7 URL representation.
use GuzzleHttp\Psr7\Request;                        // Concrete PSR-7 HTTP Request
use Psr\Http\Message\ResponseInterface;             // PSR-7 HTTP Response Interface
use Http\Client\HttpClient as HttpClientInterface;  // Interface for a PSR-7 compatible HTTP Client.

class Client {

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '4.0.0';

    /**
     * @const string The API endpoint for production.
     */
    const BASE_URL_PRODUCTION = 'https://postcodeinfo.service.justice.gov.uk';

    /**
     * Paths for API endpoints.
     */
    const PATH_LOOKUP_POSTCODE     = '/addresses';
    const PATH_LOOKUP_METADATA     = '/postcodes/%s/';


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


    /**
     * Instantiates a new PostcodeInfo client.
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

    /**
     * Returns a list of addresses associated with a postcode.
     *
     * @param $postcode
     * @return Response\AddressList
     */
    public function lookupPostcodeAddresses( $postcode ){
        
        $path = self::PATH_LOOKUP_POSTCODE;

        $response = $this->httpGet( $path, [ 'postcode' => $postcode ] );

        return Response\AddressList::buildFromResponse( $response );

    }

    /**
     * Returns metadata associated with a postcode.
     *
     * @param $postcode
     * @return Response\PostcodeInfo
     */
    public function lookupPostcodeMetadata( $postcode ){

        $path = sprintf( self::PATH_LOOKUP_METADATA, $postcode );

        $response = $this->httpGet( $path );

        return Response\PostcodeInfo::buildFromResponse( $response );

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
            'Authorization' => 'Token '.$this->apiKey,
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'POSTCODE-API-PHP-CLIENT/'.self::VERSION
        ];

    }

    //-------------------------------------------
    // GET & POST requests

    /**
     * Performs a GET against the API.
     *
     * @param string $path
     * @param array  $query
     *
     * @return ResponseInterface
     * @throw Exception\PostcodeException | Exception\ApiException | Exception\UnexpectedValueException
     */
    private function httpGet( $path, array $query = array() ){

        $url = new Uri( $this->baseUrl . $path );

        foreach( $query as $name => $value ){
            $url = Uri::withQueryValue($url, $name, $value );
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
            throw new Exception\PostcodeException( $e->getMessage(), $e->getCode(), $e );
        }

        //---

        if( $response->getStatusCode() != 200 ){
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
