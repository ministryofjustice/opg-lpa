<?php
namespace Opg\Lpa\Api\Client;

use Opg\Lpa\Api\Client\Common\Guzzle\Client as GuzzleClient;
use Opg\Lpa\Api\Client\Response\AuthResponse;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Api\Client\Response\LpaStatusResponse;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use GuzzleHttp\Message\Response;
use Opg\Lpa\Api\Client\Service\ApplicationResourceService;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;

class Client
{

    const PATH_API = 'http://localhost:8083';
    const PATH_AUTH = 'http://auth.local';

    /**
     * The API auth token
     * 
     * @var string
     */
    private $token;
    
    /**
     * The email of the logged in account
     * 
     * @var string
     */
    private $email;
    
    /**
     * The user ID of the logged in account
     *
     * @var string
     */
    private $userId;

    /**
     * 
     * @var GuzzleClient
     */
    private $guzzleClient;
    
    /**
     * The status code from the last API call
     * 
     * @var number
     */
    private $lastStatusCode;
    
    /**
     * The content body from the last API call
     * 
     * @var string
     */
    private $lastContent;
    
    /**
     * Did the last API call return with an error?
     * 
     * @var boolean
     */
    private $isError;
    
    /**
     * Create an API client for the given uri endpoint.
     * 
     * Optionally pass in a previously-obtained token. If no token is provided,
     * you will need to call the authenticate(...) function
     * 
     * @param string $token  The API auth token
     */
    public function __construct(
        $token = null
    )
    {
        $this->setToken($token);

    }

    /**
     * Returns the GuzzleClient.
     *
     * If a authentication token is available it will be preset in the HTTP header.
     *
     * @return GuzzleClient
     */
    private function client()
    {

        if( !isset($this->guzzleClient) ){
            $this->guzzleClient = new GuzzleClient();
        }

        if( $this->getToken() != null ){
            $this->guzzleClient->setToken( $this->getToken() );
        }

        return $this->guzzleClient;

    }
    
    /**
     * Register a new account
     *
     * @param array $params
     * @return string $activationToken | boolean false
     */
    public function registerAccount(
        $email,
        $password
    )
    {
        $response = $this->client()->post( self::PATH_AUTH . '/users' ,[
            'body' => [
                'username' => $email,
                'password' => $password,
            ]
        ]);
        
        if( $response->getStatusCode() != 200 ){
            return $this->log($response, false);
        }
        
        $jsonDecode = json_decode($response->getBody());
        
        if (!property_exists($jsonDecode, 'activation_token')) {
            return $this->log($response, false);
        }
        
        $this->log($response, true);
        return $jsonDecode->activation_token;
    }
    
    /**
     * Create a new LPA
     *
     * @return number The new LPA Id
     */
    public function createApplication()
    {
        $response = $this->client()->post( self::PATH_API . '/v1/users/' . $this->getUserId() . '/applications', []);
    
        if( $response->getStatusCode() != 201 ){
            return $this->log($response, false);
        }
    
        $json = $response->json();
        
        if( !isset($json['id']) ){
            return $this->log($response, false);
        }
    
        return $json['id'];
    }
    
    /**
     * Delete an LPA
     *
     * @param string $lpa
     * @param boolean $failIfDocumentNotFound
     * @return boolean
     */
    public function deleteApplication($lpaId, $succeedIfDocumentNotFound = false)
    {
        $response = $this->client()->delete( self::PATH_API . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId, [
            'headers' => ['Content-Type' => 'application/json']
        ]);
    
        $code = $response->getStatusCode();
        
        if ($code == 404) {
            return $this->log($response, $succeedIfDocumentNotFound);
        }
        
        if ($code != 204) {
            return $this->log($response, false);
        }
    
        return true;
    }
    
    /**
     * Get a single application
     *
     * @param string $lpaId
     * @return Lpa
     */
    public function getApplication($lpaId)
    {
        $response = $this->client()->get( self::PATH_API . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId, [
            'headers' => ['Content-Type' => 'application/json']
        ]);
    
        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
    
        return new Lpa($response->json());
    }
    
    /**
     * Get list of applications for the current user
     * Combine pages, if necessary
     *
     * @return array<Lpa>
     */
    public function getApplicationList()
    {
        $applicationList = array();

        $path = '/v1/users/' . $this->getUserId() . '/applications';
        
        do {
            $response = $this->client()->get( self::PATH_API . $path );
            
            $json = $response->json();
                        
            if (!isset($json['_links']) || !isset($json['_embedded']['applications'])) {
                return $this->log($response, false);
            }
       
            foreach ($json['_embedded']['applications'] as $application) {
                $applicationList[] = new Lpa($application);
            }
            
            if (isset($json['_links']['next']['href'])) {
                $path = $json['_links']['next']['href'];
            } else {
                $path = null;
            }
        } while (!is_null($path));
        
        return $applicationList;
    }

    /**
     * Activate an account from an activation token (generated at registration)
     *
     * @param string $activationToken
     * @return boolean
     */
    public function activateAccount(
        $activationToken
    )
    {
        $response = $this->client()->post( self::PATH_AUTH . '/users/activate' ,[
            'body' => [
                'activation_token' => $activationToken,
            ]
        ]);
        
        if ($response->getStatusCode() != 204) {
            return $this->log($response, false);
        }

        return $this->log($response, true);
    }
    
    /**
     * Authenticate against the authentication server and store the token
     * for future calls.
     *
     * @param string $email
     * @param string $password
     *
     * @return AuthResponse
     */
    public function authenticate(
        $email,
        $password
    )
    {

        $authResponse = new AuthResponse();

        //-------------------------
        // Authenticate the user

        $response = $this->client()->post( self::PATH_AUTH . '/token' ,[
            'body' => [
                'username' => $email,
                'password' => $password,
                'grant_type' => 'password',
            ]
        ]);

        if( $response->getStatusCode() != 200 ){
            $this->log($response, false);
            return $authResponse->setErrorDescription( "Authentication failed" );
        }

        $authResponse->exchangeJson( $response->getBody() );

        $this->setToken( $authResponse->getToken() );
        $this->setEmail( $email );

        //-------------------------
        // Get the user's details

        $response = $this->client()->get( self::PATH_AUTH . '/tokeninfo', [
            'query' => [ 'access_token' => $this->getToken() ]
        ]);

        if( $response->getStatusCode() != 200 ){
            $this->log($response, false);
            return $authResponse->setErrorDescription( "Authentication failed" );
        }
        
        $responseJson = $response->json();

        if( !isset($responseJson['user_id']) ){
            $this->log($response, false);
            return $authResponse->setErrorDescription( "Authentication failed" );
        }

        $this->log($response, true);
        $authResponse->setUserId( $responseJson['user_id'] );
        
        $this->setUserId($responseJson['user_id']);

        //---

        return $authResponse;
    }
    
    /**
     * Get the email from a token
     * 
     * @param string token
     * @return string|boolean User email or false if token invalid
     */
    public function getEmailFromToken($token)
    {
        $response = $this->client()->get( self::PATH_AUTH . '/tokeninfo', [
            'query' => [ 'access_token' => $token ]
        ]);
        
        if( $response->getStatusCode() != 200 ){
            return false;
        }
        
        $response = $response->json();
        
        if( !isset($response['user_id']) ){
            return false;
        }
        
        return $response['username'];
    }
    
    /**
     * Set the email and user id from the current token
     *
     * @return boolean
     */
    private function setEmailAndUserIdFromToken()
    {
        $response = $this->client()->get( self::PATH_AUTH . '/tokeninfo', [
            'query' => [ 'access_token' => $this->token ]
        ]);
    
        if( $response->getStatusCode() != 200 ){
            return false;
        }
    
        $response = $response->json();
    
        if( !isset($response['user_id']) ){
            return false;
        }
        
        if( !isset($response['username']) ){
            return false;
        }
    
        $this->setEmail($response['username']);
        $this->setUserId($response['user_id']);
        
        return true;
    }
    
    /**
     * Delete an account from the auth server, delete the user from
     * the account server and delete all the account's LPAs
     *
     * @param $authToken
     * @return boolean
     */
    public function deleteUserAndAllTheirLpas($authToken)
    {
        $response = $this->client()->get( self::PATH_AUTH . '/deregister', [
            'headers' => ['Token' => $authToken]
        ]);
        
        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
        
        return true;
    }
    
    /**
     * Request a password reset. API server will send email with further instructions.
     *
     * @param string $email
     * @return Client $this
     */
    public function resetPassword($email)
    {
        return $this;
    }
    
    /**
     * Update auth email
     *
     * @param string $newEmail
     * 
     * @return boolean
     */
    public function updateAuthEmail(
        $newEmail
    )
    {
        $response = $this->client()->post( self::PATH_AUTH . '/users/' . $this->getEmail() . '/put', [
            'body' => ['new_email' => $newEmail],
            'headers' => ['Token' => $this->getToken()]
        ]);
        
        if ($response->getStatusCode() != 204) {
            return $this->log($response, false);
        }
        
        return true;
    }
    
    /**
     * Update auth password
     *
     * @param string $newPassword
     * 
     * @return boolean
     */
    public function updateAuthPassword(
        $newPassword
    )
    {
        $response = $this->client()->post( self::PATH_AUTH . '/users/' . $this->getEmail() . '/put', [
            'body' => ['new_password' => $newPassword],
            'headers' => ['Token' => $this->getToken()]
        ]);
        
        if ($response->getStatusCode() != 204) {
            return $this->log($response, false);
        }
        
        return true;
    }
    
    /**
     * Set user's about me details
     *
     * @param array $params
     * @return Client $this
     */
    public function setAboutMe(
        User $user
    )
    {
        $response = $this->client()->put( self::PATH_API . '/v1/users/' . $this->getUserId(), [
            'body' => $user->toJson(),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
        
        return true;
    }
    
    /**
     * Get the user's about me details
     *
     * @return User|boolean
     */
    public function getAboutMe()
    {
        $response = $this->client()->get( self::PATH_API . '/v1/users/' . $this->getUserId(), [
            'headers' => ['Content-Type' => 'application/json']
        ]);
        
        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
        
        return new User($response->json());
    }
    
    /**
     * @return member variable $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        
        return $this;
    }
    
    /**
     * The current validation / state of the LPA. Includes whether the LPA is
     * currently locked, is valid (i.e. complete), and general metadata.
     * 
     * @param Lpa $lpa
     * @return \Opg\Lpa\Api\Client\Response\LpaStatusResponse
     */
    public function getStatus(Lpa $lpa)
    {
        return new LpaStatusResponse();
    }
    
    /**
     * Return the type of the LPA
     * 
     * If property not yet set, return null
     * If error, return false
     * Else, return type of LPA - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * 
     * @param string $lpaId
     * @return boolean|null|string
     */
    public function getType($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'type', $this);
        return $helper->getSingleValueResource('type');
    }
    
    /**
     * Set the LPA type
     * 
     * @param string $lpaId
     * @param number $lpaTypeId  - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * @return boolean
     */
    public function setType($lpaId, $lpaType)
    {
        $helper = new ApplicationResourceService($lpaId, 'type', $this);
        return $helper->setResource(json_encode(['type' => $lpaType]));
    }
    
    /**
     * Delete the type from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteType($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'type', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Return the instructions of the LPA
     * 
     * If property not yet set, return null
     * If error, return false
     * Else, return instructions of LPA - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * 
     * @param string $lpaId
     * @return boolean|null|string
     */
    public function getInstructions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'instruction', $this);
        return $helper->getSingleValueResource('instruction');
    }
    
    /**
     * Set the LPA instructions
     * 
     * @param string $lpaId
     * @param number $lpaInstructionsId  - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * @return boolean
     */
    public function setInstructions($lpaId, $lpaInstructions)
    {
        $helper = new ApplicationResourceService($lpaId, 'instruction', $this);
        return $helper->setResource(json_encode(['instruction' => $lpaInstructions]));
    }
    
    /**
     * Delete the instructions from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteInstructions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'instruction', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Return the preferences 
     * 
     * If property not yet set, return null
     * If error, return false
     * Else, return preferences
     * 
     * @param string $lpaId
     * @return boolean|null|string
     */
    public function getPreferences($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'preference', $this);
        return $helper->getSingleValueResource('preference');    
    }
    
    /**
     * Set the LPA preferences
     * 
     * @param string $lpaId
     * @param number $preferences
     * @return boolean
     */
    public function setPreferences($lpaId, $preferences)
    {
        $helper = new ApplicationResourceService($lpaId, 'preference', $this);
        return $helper->setResource(json_encode(['preference' => $preferences]));
    }
    
    /**
     * Delete the type from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deletePreferences($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'preference', $this);
        return $helper->deleteResource();
    }

    /**
     * Get the primary attorney decisions
     *
     * @param string $lpaId
     * @return PrimaryAttorneyDecisions
     */
    public function getPrimaryAttorneyDecisions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorney-decisions', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions');
    }
    
    /**
     * Set the primary attorney decisions
     *
     * @param string $lpaId
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     * @return boolean
     */
    public function setPrimaryAttorneyDecisions(
        $lpaId,
        PrimaryAttorneyDecisions $primaryAttorneyDecisions
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorney-decisions', $this);
        return $helper->setResource($primaryAttorneyDecisions->toJson());
    }
    
    /**
     * Delete the primary attorney decisions
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deletePrimaryAttorneyDecisions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorney-decisions', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Get the replacement attorney decisions
     *
     * @param string $lpaId
     * @return ReplacementAttorneyDecisions
     */
    public function getReplacementAttorneyDecisions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorney-decisions', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions');
    }
    
    /**
     * Set the replacement attorney decisions
     *
     * @param string $lpaId
     * @param ReplacementAttorneyDecisions $replacementAttorneyDecisions
     * @return boolean
     */
    public function setReplacementAttorneyDecisions(
        $lpaId,
        ReplacementAttorneyDecisions $replacementAttorneyDecisions
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorney-decisions', $this);
        return $helper->setResource($replacementAttorneyDecisions->toJson());
    }
    
    /**
     * Delete the replacement attorney decisions
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteReplacementAttorneyDecisions($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorney-decisions', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Get the donor
     *
     * @param string $lpaId
     * @return Donor
     */
    public function getDonor($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'donor', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Donor');
    }
    
    /**
     * Set the donor
     *
     * @param string $lpaId
     * @param Donor $donor
     * @return boolean
     */
    public function setDonor(
        $lpaId,
        Donor $donor
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'donor', $this);
        return $helper->setResource($donor->toJson());
    }
    
    /**
     * Delete the donor
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteDonor($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'donor', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Get the correspondent
     *
     * @param string $lpaId
     * @return Correspondence
     */
    public function getCorrespondent($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'correspondent', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Correspondence');
    }
    
    /**
     * Set the correspondent
     *
     * @param string $lpaId
     * @param Correspondence $correspondent
     * @return boolean
     */
    public function setCorrespondent(
        $lpaId,
        Correspondence $correspondent
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'correspondent', $this);
        return $helper->setResource($correspondent->toJson());
    }
    
    /**
     * Delete the correspondent
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCorrespondent($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'correspondent', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Get the payment information
     *
     * @param string $lpaId
     * @return Correspondence
     */
    public function getPayment($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'payment', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Payment\Payment');
    }
    
    /**
     * Set the payment information
     *
     * @param string $lpaId
     * @param Payment $payment
     * @return boolean
     */
    public function setPayment(
        $lpaId,
        Payment $payment
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'payment', $this);
        return $helper->setResource($payment->toJson());
    }
    
    /**
     * Delete the payment information
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deletePayment($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'payment', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Returns whether or not the Who Are You information has been provided
     * 
     * @return boolean
     */
    public function isWhoAreYouSet()
    {
        return false;
    }
    
    /**
     * Sets the person/organisation of who completed the application
     * 
     * @param unknown $lpaId
     * @param WhoAreYou $whoAreYou
     * @return boolean
     */
    public function setWhoAreYou(
        $lpaId,
        WhoAreYou $whoAreYou
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'who-are-you', $this);
        return $helper->addResource($whoAreYou->toJson());
    }
    
    /**
     * Returns whether the LPA is currently locked
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function isLpaLocked($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'lock', $this);
        return $helper->getSingleValueResource('locked');
    }
    
    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function lockLpa($lpaId)
    {
        $uri = self::PATH_API . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId . '/lock';
        
        $response = $this->client()->post( $uri );
        
        if( $response->getStatusCode() != 201 ) {
            return $this->log($response, false);
        }
        
        $json = $response->json();
        
        if( !isset($json['locked']) ){
            return $this->log($response, false);
        }
        
        return $json['locked'];
    }
        
    /**
     * Returns the id of the seed LPA document and the list of actors
     * 
     * @param string $lpaId
     * @return array [id=>,donor=>,attorneys=>[],certificateProviders=>,notifiedPersons=>[],correspondent=>]
     */
    public function getSeedDetails($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'seed', $this);
        return $helper->getSingleValueResource('seed');
    }
    
    /**
     * Sets the id of the seed LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function setSeed($lpaId, $seedId)
    {
        $helper = new ApplicationResourceService($lpaId, 'seed', $this);
        return $helper->setResource(json_encode(['seed' => $seedId]));
    }
 
    /**
     * Deletes the seed reference from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteSeed($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'seed', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Returns a list of all currently set attorneys
     * 
     * @param string $lpaId
     * @return array
     */
    public function getAttorneys($lpaId)
    {
        return [];
    }

    /**
     * Adds a new attorney
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function addAttorney($lpaId)
    {
        return true;
    }
    
    /**
     * Returns the attorney for the given attorney id
     * 
     * @param string $lpaId
     * @param string $attorneyId
     * @return \Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney
     */
    public function getAttorney(
        $lpaId,
        $attorneyId
    )
    {
        return null;
    }
    
    /**
     * Sets the attorney for the given attorney id
     * 
     * @param string $lpaId
     * @param string $attorneyId
     * @return boolean
     */
    public function setAttorney(
        $lpaId,
        $attorneyId
    )
    {
        return true;
    }
    
    /**
     * Deletes the attorney for the given attorney id
     *
     * @param string $lpaId
     * @param string $attorneyId
     * @return boolean
     */
    public function deleteAttorney(
        $lpaId,
        $attorneyId
    )
    {
        return true;
    }
    
    /**
     * Returns a list of all currently set notified persons
     *
     * @param string $lpaId
     * @return array
     */
    public function getNotifiedPersons($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'notified-people', $this);
        return $helper->getResourceList('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
    }
    
    /**
     * Adds a new notified person
     *
     * @param string $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @return boolean
     */
    public function addNotifiedPerson(
        $lpaId,
        NotifiedPerson $notifiedPerson
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'notified-people', $this);
        return $helper->addResource($notifiedPerson->toJson());
    }
    
    /**
     * Returns the notified person for the given notified person id
     *
     * @param string $lpaId
     * @param string $notifiedPersonId
     * @return \Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson
     */
    public function getNotifiedPerson(
        $lpaId,
        $notifiedPersonId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'notified-people', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson', $notifiedPersonId);
    }
    
    /**
     * Sets the notified person for the given notified person id
     *
     * @param string $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function setNotifiedPerson(
        $lpaId,
        $notifiedPerson,
        $notifiedPersonId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'notified-people', $this);
        return $helper->setResource($notifiedPerson->toJson(), $notifiedPersonId);
    }
    
    /**
     * Deletes the person to notify for the given notified person id
     * 
     * @param string $lpaId
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function deleteNotifiedPerson(
        $lpaId,
        $notifiedPersonId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'notified-people', $this);
        return $helper->deleteResource($notifiedPersonId);
    }
    
    /**
     * Returns a list of all currently set primary attorneys
     *
     * @param string $lpaId
     * @return array
     */
    public function getPrimaryAttorneys($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->getResourceList('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
    }
    
    /**
     * Adds a new primary attorney
     *
     * @param string $lpaId
     * @param PrimaryAttorney $primaryAttorney
     * @return boolean
     */
    public function addPrimaryAttorney(
        $lpaId,
        AbstractAttorney $primaryAttorney
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->addResource($primaryAttorney->toJson());
    }
    
    /**
     * Returns the primary attorney for the given primary attorney id
     *
     * @param string $lpaId
     * @param string $primaryAttorneyId
     * @return \Opg\Lpa\DataModel\Lpa\Document\PrimaryAttorney
     */
    public function getPrimaryAttorney(
        $lpaId,
        $primaryAttorneyId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human', $primaryAttorneyId);
    }
    
    /**
     * Sets the primary attorney for the given primary attorney id
     *
     * @param string $lpaId
     * @param AbstractAttorney $primaryAttorney
     * @param string $primaryAttorneyId
     * @return boolean
     */
    public function setPrimaryAttorney(
        $lpaId,
        AbstractAttorney $primaryAttorney,
        $primaryAttorneyId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->setResource($primaryAttorney->toJson(), $primaryAttorneyId);
    }
    
    /**
     * Deletes the person to notify for the given primary attorney id
     *
     * @param string $lpaId
     * @param string $primaryAttorneyId
     * @return boolean
     */
    public function deletePrimaryAttorney(
        $lpaId,
        $primaryAttorneyId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->deleteResource($primaryAttorneyId);
    }
    
    /**
     * Get the certificate provider
     *
     * @param string $lpaId
     * @return CertificateProvider
     */
    public function getCertificateProvider($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'certificate-provider', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider');
    }
    
    /**
     * Set the certificate provider
     *
     * @param string $lpaId
     * @param CertificateProvider $certificateProvider
     * @return boolean
     */
    public function setCertificateProvider(
        $lpaId,
        $certificateProvider
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'certificate-provider', $this);
        return $helper->setResource($certificateProvider->toJson());
    }
    
    /**
     * Delete the certificate provider
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCertificateProvider($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'certificate-provider', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Returns a list of all currently available PDFs, with a href to download them
     * 
     * @return array
     */
    public function getPdfList()
    {
        return [];
    }
    
    /**
     * Returns the PDF specified by name. If the required data is present
     * 
     * @param string $lpaId
     * @param string $pdfName
     * @return string - The PDF stream
     */
    public function getPdf(
        $lpaId,
        $pdfName
    )
    {
        return null;
    }

    /**
     * @return the $lastStatusCode
     */
    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    /**
     * @param number $lastStatusCode
     */
    private function setLastStatusCode($lastStatusCode)
    {
        $this->lastStatusCode = $lastStatusCode;
    }

     /**
     * @return the $lastContent
     */
    public function getLastContent()
    {
        return $this->lastContent;
    }

    /**
     * @param string $lastContent
     */
    private function setLastContent($lastContent)
    {
        $this->lastContent = $lastContent;
    }
    
    /**
     * @return $isError
     */
    public function isError()
    {
        return $this->isError;
    }
    
    /**
     * @param boolean $isError
     */
    private function setIsError($isError)
    {
        $this->isError = $isError;
    }

    /**
     * @return the $email
     */
    public function getEmail()
    {
        if (is_null($this->email) && !is_null($this->token)) {
            $this->setEmailAndUserIdFromToken();
        }
        
        return $this->email;
    }
    
    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    
    /**
     * @return the $userId
     */
    public function getUserId()
    {
        if (is_null($this->userId) && !is_null($this->token)) {
            $this->setEmailAndUserIdFromToken();
        }
        
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Log the response of the API call and set some internal member vars
     * If content body is JSON, convert it to an array
     * 
     * @param Response $response
     * @param string $isSuccess
     * @return boolean
     * 
     * @todo - External logging
     */
    public function log(Response $response, $isSuccess=true)
    {
        $this->setLastStatusCode($response->getStatusCode());
        
        $responseBody = $response->getBody()->getContents();
        $jsonDecoded = json_decode($responseBody, true);
        
        if (json_last_error() == JSON_ERROR_NONE) {
            $this->setLastContent($jsonDecoded);
        } else {
            $this->setLastContent($responseBody);
        }
        
        
        $this->setIsError(!$isSuccess);
        
        return $isSuccess;
    }
}
