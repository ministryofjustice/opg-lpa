<?php
namespace Opg\Lpa\Api\Client;

use GuzzleHttp\Psr7\Uri;                            // Concrete PSR-7 URL representation.
use GuzzleHttp\Psr7\Request;                        // Concrete PSR-7 HTTP Request
use Psr\Http\Message\UriInterface;                  // PSR-7 URI Interface
use Psr\Http\Message\ResponseInterface;             // PSR-7 HTTP Response Interface
use Http\Client\HttpClient as HttpClientInterface;  // Interface for a PSR-7 compatible HTTP Client.

class Client {
    use ClientGuzzleTrait;

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '3.1.1';


    /**
     * The base URI for the API
     */
    private $apiBaseUri = 'https://apiv2';

    /**
     * The base URI for the auth server
     */
    private $authBaseUri = 'https://authv2';

    /**
     * @var HttpClientInterface PSR-7 compatible HTTP Client
     */
    private $httpClient;

    //------------------------------
    // User specific properties

    /**
     * The user ID of the logged in account
     *
     * @var string
     */
    private $userId;

    /**
     * The API auth token
     *
     * @var string
     */
    private $token;

    //------------------------------------------------------------------------------------
    // Public Auth access methods

    /**
     * Authenticate the user with an email address and password.
     *
     * @param $email
     * @param $password
     * @return Response\AuthResponse
     */
    public function authenticate( $email, $password ){

        $url = new Uri( $this->getAuthBaseUri() . '/v1/authenticate' );

        try {

            $response = $this->httpPost( $url, [
                'Username' => strtolower($email),
                'Password' => $password,
            ]);

            if( $response->getStatusCode() == 200 ){

                $authResponse = Response\AuthResponse::buildFromResponse( $response );

                $this->setUserId( $authResponse->getUserId() );
                $this->setToken( $authResponse->getToken() );
                $this->setEmail( $authResponse->getUsername() );

                return $authResponse;

            }

        } catch ( Exception\ResponseException $e ){

            switch ( $e->getDetail() ) {
                case 'account-locked/max-login-attempts':
                    return (new Response\AuthResponse)->setErrorDescription( 'locked' );
                case 'account-not-active':
                    return (new Response\AuthResponse)->setErrorDescription( 'not-activated' );
            }

        }

        return (new Response\AuthResponse)->setErrorDescription( 'authentication-failed' );

    } // function


    /**
     * Registers an (unactivated) account with Auth and returns the activation token as a string.
     *
     * @param $email
     * @param $password
     * @return string|\Exception|Exception\ResponseException
     */
    public function registerAccount( $email, $password ) {

        $url = new Uri( $this->getAuthBaseUri() . '/v1/users' );

        try {

            $response = $this->httpPost( $url, [
                'Username' => strtolower($email),
                'Password' => $password,
            ]);

            if( $response->getStatusCode() == 200 ){

                $body = json_decode($response->getBody(), true);

                if( isset($body['activation_token']) ){

                    return $body['activation_token'];

                }

            }

        } catch ( Exception\ResponseException $e ){
            return $e;
        }

        return new Response\Error( 'unknown-error' );

    } // function


    /**
     * Activates an account using an Activation Token.
     *
     * @param $activationToken
     * @return bool|\Exception|Exception\ResponseException|Response\Error
     */
    public function activateAccount( $activationToken ) {

        $url = new Uri( $this->getAuthBaseUri() . '/v1/users/activate' );

        try {

            $response = $this->httpPost( $url, [
                'Token' => $activationToken,
            ]);

            if( $response->getStatusCode() == 204 ){

                return true;

            }

        } catch ( Exception\ResponseException $e ){
            return $e;
        }

        return new Response\Error( 'unknown-error' );

    } // function


    /**
     * Returns user account details for a passed authentication token.
     *
     * @param $token
     * @return array|Exception\ResponseException|Response\Error
     */
    public function getTokenInfo( $token ) {

        $url = new Uri( $this->getAuthBaseUri() . '/v1/authenticate' );

        try {

            $response = $this->httpPost( $url, [
                'Token' => $token,
            ]);

            if( $response->getStatusCode() == 200 ){

                $body = json_decode($response->getBody(), true);

                if( is_array($body) ){
                    return $body;
                }

            }

        } catch ( Exception\ResponseException $e ){
            return $e;
        }

        return new Response\Error( 'unknown-error' );

    }

    /*
    public function getEmailFromToken( $token ) {

    }

    private function setEmailAndUserIdFromToken(){

    }

    public function deleteUserAndAllTheirLpas(){

    }

    public function requestPasswordReset( $email ) {

    }

    public function updateAuthPasswordWithToken( $token, $newPassword ) {

    }

    public function requestEmailUpdate( $newEmailAddress ) {

    }

    public function updateAuthEmail( $emailUpdateToken ) {

    }

    public function updateAuthPassword( $currentPassword, $newPassword ){

    }
    */

    //------------------------------------------------------------------------------------
    // Public API access methods

    public function getApplication( $lpaId ){

        $path = sprintf( '/v2/users/%s/applications/%d', $this->getUserId(), $lpaId );

        $url = new Uri( $this->getApiBaseUri() . $path );

        $response = $this->httpGet( $url );

        return ( $response->getStatusCode() == 200 ) ? Response\Lpa::buildFromResponse($response) : false;

    }

    public function updateApplication( $lpaId, Array $data ){

        $path = sprintf( '/v2/users/%s/applications/%d', $this->getUserId(), $lpaId );

        $url = new Uri( $this->getApiBaseUri() . $path );

        $response = $this->httpPatch($url, $data);

        return Response\Lpa::buildFromResponse( $response );

    }

    /**
     * Sets the LPA's metadata
     *
     * Setting metadata is a special case as we need to merge client side at present.
     * 
     * NB: This is not a deep level merge.
     *
     * @param string $lpaId
     * @param array $metadata
     * @return boolean
     */
    public function setMetaData($lpaId, Array $metadata) {

        $currentMetadata = $this->getMetaData($lpaId);

        if( is_array($currentMetadata) ){

            // Strip out the _links key
            unset( $currentMetadata['_links'] );

            // Merge new data into old
            $metadata = array_merge( $currentMetadata, $metadata );

        }

        //---

        $this->updateApplication($lpaId, [ 'metadata'=>$metadata ]);

        return true;

    }

    //------------------------------------------------------------------------------------
    // Internal API access methods

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders(){

        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'LPA-API-PHP-CLIENT/'.self::VERSION
        ];

        if( $this->getToken() != null ){
            $headers['Token'] = $this->getToken();
        }

        return $headers;

    }


    private function httpGet( Uri $url, array $query = array() ){

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

            $this->setLastStatusCode( $response->getStatusCode() );

        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        //---

        if( !in_array($response->getStatusCode(), [200, 404]) ){
            throw $this->createErrorException( $response );
        }

        return $response;

    }
    
    private function httpPost( Uri $url, Array $payload ){

        $request = new Request(
            'POST',
            $url,
            $this->buildHeaders(),
            json_encode($payload)
        );

        try {

            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode( $response->getStatusCode() );

        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        //---

        if( !in_array($response->getStatusCode(), [200, 201, 204]) ){
            throw $this->createErrorException( $response );
        }

        return $response;

    }

    private function httpPatch( Uri $url, Array $payload ){

        $request = new Request(
            'PATCH',
            $url,
            $this->buildHeaders(),
            json_encode($payload)
        );

        try {

            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode( $response->getStatusCode() );

        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
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
     * @return Exception\ResponseException
     */
    protected function createErrorException( ResponseInterface $response ){

        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ResponseException( $message, $response->getStatusCode(), $response );

    }

    //------------------------------------------------------------------------------------
    // Getters and setters

    /**
     * @return string
     */
    public function getUserId()
    {
        if (is_null($this->userId) && !is_null($this->token)) {
            $this->setEmailAndUserIdFromToken();
        }

        return $this->userId;
    }

    /**
     * @return string
     */
    public function getAuthBaseUri()
    {
        return $this->authBaseUri;
    }

    /**
     * @return string
     */
    public function getApiBaseUri(){
        return $this->apiBaseUri;
    }

    /**
     * @return string
     */
    final public function getToken(){
        return $this->token;
    }

    /**
     * @param $token string
     * @return $this
     */
    final public function setToken($token){
        $this->token = $token;
        return $this;
    }

    /**
     * @return HttpClientInterface
     * @throws \UnexpectedValueException
     */
    final protected function getHttpClient(){

        if( !( $this->httpClient instanceof HttpClientInterface ) ){

            // @todo - For now create this using Guzzle v5.
            // This should be removed when the tight couple to Guzzle v5 is removed.

            $this->httpClient = new \Http\Adapter\Guzzle5\Client(
                new \GuzzleHttp\Client,
                new \Http\Message\MessageFactory\GuzzleMessageFactory
            );

            //throw new \UnexpectedValueException('Invalid HttpClient set');
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
