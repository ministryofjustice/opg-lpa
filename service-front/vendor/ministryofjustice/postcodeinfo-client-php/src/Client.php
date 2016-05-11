<?php
namespace MinistryOfJustice\PostcodeInfo;

use GuzzleHttp\Psr7\Uri;                            // Concrete PSR-7 URL representation.
use GuzzleHttp\Psr7\Request;                        // Concrete PSR-7 HTTP Request
use Http\Client\HttpClient as HttpClientInterface;  // Interface for a PSR-7 compatible HTTP Client.

class Client {

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '4.0.0';

    /**
     * @const string The API endpoint for Pay production.
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
    
    //------------------------------------------------------------------------------------
    // Lookup methods

    /**
     * Lookup information for the given postcode
     * and return the contents in a Postcode object
     *
     * @param  string $postcode
     * @return Postcode
     */
    public function lookupPostcode($postcode){

        $url = new Uri( $this->baseUrl . self::PATH_LOOKUP_POSTCODE );

        $url = Uri::withQueryValue($url, 'postcode', $postcode );

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

        $postcodeObj = new Postcode();

        if( $response->getStatusCode() != 200 ){

            $postcodeObj->setIsValid(false);
            return $postcodeObj;

        }

        //---

        $data = json_decode($response->getBody(), true);

        foreach ($data as $addressData) {
            $address = new Address();
            $address->exchangeArray($addressData);
            $postcodeObj->addAddress($address);
        }

        if (count($data) > 0) {
            $postcodeObj->setIsValid(true);
            $postcodeObj = $this->addGeneralInformation($postcodeObj, $postcode);
        } else {
            $postcodeObj->setIsValid(false);
        }

        return $postcodeObj;

    }


    /**
     * Get general information for the postcode area (local authority, centre point)
     *
     * @param  Postcode $postcodeObj
     * @return Postcode
     */
    public function addGeneralInformation(Postcode $postcodeObj, $postcode){

        $path = sprintf( self::PATH_LOOKUP_METADATA, $postcode );

        $url = new Uri( $this->baseUrl . $path );

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

            $postcodeObj->setIsValid(false);
            return $postcodeObj;

        }

        //---

        $responseArray = json_decode($response->getBody(), true);

        if (count($responseArray) > 0) {

            if (isset($responseArray['centre']) && $responseArray['centre'] != null) {

                $centrePoint = new Point();

                $centrePoint->setType($responseArray['centre']['type']);
                $centrePoint->setLongitude($responseArray['centre']['coordinates'][0]);
                $centrePoint->setLatitude($responseArray['centre']['coordinates'][1]);

                $postcodeObj->setCentrePoint($centrePoint);
            }

            if (isset($responseArray['local_authority']) && $responseArray['local_authority'] != null) {

                $localAuthority = new LocalAuthority();

                $localAuthority->setName($responseArray['local_authority']['name']);
                $localAuthority->setGssCode($responseArray['local_authority']['gss_code']);

                $postcodeObj->setLocalAuthority($localAuthority);
            }

        } else {
            $postcodeObj->setIsValid(false);
        }

        return $postcodeObj;

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
