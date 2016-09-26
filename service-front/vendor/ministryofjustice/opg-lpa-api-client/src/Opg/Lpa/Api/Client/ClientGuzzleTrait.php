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

trait ClientGuzzleTrait
{
    
    /**
     * The email of the logged in account
     *
     * Deprecated in v4
     * 
     * @var string
     */
    private $email;

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
     * @param field_type $apiBaseUri
     */
    public function setApiBaseUri($apiBaseUri)
    {
        $this->apiBaseUri = $apiBaseUri;
    }

    /**
     * @param field_type $authBaseUri
     */
    public function setAuthBaseUri($authBaseUri)
    {
        $this->authBaseUri = $authBaseUri;
    }

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
     * Create a new LPA
     *
     * @return number The new LPA Id
     */
    public function createApplication()
    {
        $response = $this->client()->post( $this->apiBaseUri . '/v1/users/' . $this->getUserId() . '/applications', []);
    
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
        $response = $this->client()->delete( $this->apiBaseUri . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId, [
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
    /*
    public function getApplication($lpaId)
    {
        $response = $this->client()->get( $this->apiBaseUri . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId, [
            'headers' => ['Content-Type' => 'application/json']
        ]);
    
        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
    
        return new Lpa($response->json());
    }
    */
    
    /**
     * Get list of applications for the current user
     * Combine pages, if necessary
     *
     * @return array<Lpa>
     */
    public function getApplicationList( $query = null )
    {
        $applicationList = array();

        $path = '/v1/users/' . $this->getUserId() . '/applications';
        
        do {
            $response = $this->client()->get( $this->apiBaseUri . $path, [
                'query' => [ 'search' => $query ]
            ]);

            if ($response->getStatusCode() != 200) {
                return $this->log($response, false);
            }
            
            $json = $response->json();
            
            if ($json['count'] == 0) {
                return [];
            }
                        
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
     * Delete all the LPAs from an account
     */
    public function deleteAllLpas()
    {
        $response = $this->client()->delete( $this->apiBaseUri . '/v1/users/' . $this->getUserId(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Token' => $this->getToken(),
            ],
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
        $response = $this->client()->put( $this->apiBaseUri . '/v1/users/' . $this->getUserId(), [
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
        $response = $this->client()->get( $this->apiBaseUri . '/v1/users/' . $this->getUserId(), [
            'headers' => ['Content-Type' => 'application/json']
        ]);
        
        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }
        
        return new User($response->json());
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        return new LpaStatusResponse();
    }
    
    /**
     * Return the repeat case number
     *
     * If property not yet set, return null
     * If error, return false
     *
     * @param string $lpaId
     * @return boolean|null|string
     */
    public function getRepeatCaseNumber($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'repeat-case-number', $this);
        return $helper->getSingleValueResource('repeatCaseNumber');
    }
    
    /**
     * Set the LPA type
     *
     * @param string $lpaId
     * @param number $repeatCaseNumber
     * @return boolean
     */
    public function setRepeatCaseNumber($lpaId, $repeatCaseNumber)
    {
        $helper = new ApplicationResourceService($lpaId, 'repeat-case-number', $this);
        return $helper->setResource(json_encode(['repeatCaseNumber' => $repeatCaseNumber]));
    }
    
    /**
     * Delete the type from the LPA
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteRepeatCaseNumber($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'repeat-case-number', $this);
        return $helper->deleteResource();
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
     * Update (patch) the replacement attorney decisions
     *
     * @param string $lpaId
     * @param Array $replacementAttorneyDecisions
     * @return boolean
     */
    public function updateReplacementAttorneyDecisions(
        $lpaId,
        Array $replacementAttorneyDecisions
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorney-decisions', $this);
        return $helper->updateResource( json_encode($replacementAttorneyDecisions) );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
     * @param unknown $lpaId
     * @return boolean
     */
    public function isWhoAreYouSet($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'who-are-you', $this);
        return $helper->getSingleValueResource('whoAreYouAnswered');
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        $uri = $this->apiBaseUri . '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId . '/lock';
        
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
        return $helper->getRawJson();
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
     * Returns a list of all currently set notified persons
     *
     * @param string $lpaId
     * @return array
     */
    public function getNotifiedPersons($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
        return $helper->getResourceList('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney');
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
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'primary-attorneys', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney', $primaryAttorneyId);
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
     * Returns a list of all currently set replacement attorneys
     *
     * @param string $lpaId
     * @return array
     */
    public function getReplacementAttorneys($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorneys', $this);
        return $helper->getResourceList('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney');
    }
    
    /**
     * Adds a new replacement attorney
     *
     * @param string $lpaId
     * @param ReplacementAttorney $replacementAttorney
     * @return boolean
     */
    public function addReplacementAttorney(
        $lpaId,
        AbstractAttorney $replacementAttorney
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorneys', $this);
        return $helper->addResource($replacementAttorney->toJson());
    }
    
    /**
     * Returns the replacement attorney for the given replacement attorney id
     *
     * @param string $lpaId
     * @param string $replacementAttorneyId
     * @return \Opg\Lpa\DataModel\Lpa\Document\ReplacementAttorney
     */
    public function getReplacementAttorney(
        $lpaId,
        $replacementAttorneyId
    )
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorneys', $this);
        return $helper->getEntityResource('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney', $replacementAttorneyId);
    }
    
    /**
     * Sets the replacement attorney for the given replacement attorney id
     *
     * @param string $lpaId
     * @param AbstractAttorney $replacementAttorney
     * @param string $replacementAttorneyId
     * @return boolean
     */
    public function setReplacementAttorney(
        $lpaId,
        AbstractAttorney $replacementAttorney,
        $replacementAttorneyId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorneys', $this);
        return $helper->setResource($replacementAttorney->toJson(), $replacementAttorneyId);
    }
    
    /**
     * Deletes the person to notify for the given replacement attorney id
     *
     * @param string $lpaId
     * @param string $replacementAttorneyId
     * @return boolean
     */
    public function deleteReplacementAttorney(
        $lpaId,
        $replacementAttorneyId
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'replacement-attorneys', $this);
        return $helper->deleteResource($replacementAttorneyId);
    }
    
    /**
     * Get the certificate provider
     *
     * @param string $lpaId
     * @return CertificateProvider
     */
    public function getCertificateProvider($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
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
     * Get Who Is Registering
     *
     * @param string $lpaId
     * @return string|array
     */
    public function getWhoIsRegistering($lpaId)
    {
        die( 'Unused. If you see this tell Neil. - ' . __METHOD__ );
        $helper = new ApplicationResourceService($lpaId, 'who-is-registering', $this);
        $result = $helper->getSingleValueResource('who');

        if( is_array($result) ){

            // If it's an array, returns instances of Attorneys
            $result = array_map( function( $v ){
                return AbstractAttorney::factory( $v );
            }, $result );

        }

        return $result;

    }

    /**
     * Set Who Is Registering
     *
     * @param string $lpaId
     * @param string|array $who
     * @return boolean
     */
    public function setWhoIsRegistering(
        $lpaId,
        $who
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'who-is-registering', $this);
        return $helper->setResource( json_encode([ 'who' => $who ]) );
    }

    /**
     * Delete Who Is Registering
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteWhoIsRegistering($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'who-is-registering', $this);
        return $helper->deleteResource();
    }
    
    /**
     * Returns the PDF details for the specified PDF type
     *
     * @param string $lpaId
     * @param string $pdfName
     */
    public function getPdfDetails(
        $lpaId,
        $pdfName
    )
    {
        $helper = new ApplicationResourceService($lpaId, 'pdfs/' . $pdfName, $this);
        $resource = $helper->getResource();
        
        $json = $resource->getBody();
        
        $array = json_decode($json, true);
        return $array;
    }
    
    /**
     * Returns the PDF body for the specified PDF type
     *
     * @param string $lpaId
     * @param string $pdfName
     */
    public function getPdf(
        $lpaId,
        $pdfName
    )
    {
        $path = '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId . '/pdfs/' . $pdfName . '.pdf';
        
        $response = $this->client()->get( $this->apiBaseUri . $path );
        
        $code = $response->getStatusCode();
        
        if ($code != 200) {
            return $this->log($response, false);
        }
        
        return $response->getBody();
    }
    
    /**
     * Get list of pdfs for the given LPA
     * Combine pages, if necessary
     *
     * @param string $lpaId
     *
     * @return array
     */
    public function getPdfList($lpaId)
    {
        $pdfList = array();
    
        $path = '/v1/users/' . $this->getUserId() . '/applications/' . $lpaId . '/pdfs';
    
        do {
            $response = $this->client()->get( $this->apiBaseUri . $path );
    
            if ($response->getStatusCode() != 200) {
                return $this->log($response, false);
            }
    
            $json = $response->json();
    
            if ($json['count'] == 0) {
                return [];
            }
    
            if (!isset($json['_links']) || !isset($json['_embedded']['pdfs'])) {
                return $this->log($response, false);
            }
             
            foreach ($json['_embedded']['pdfs'] as $pdf) {
                $pdfList[] = $pdf;
            }
    
            if (isset($json['_links']['next']['href'])) {
                $path = $json['_links']['next']['href'];
            } else {
                $path = null;
            }
        } while (!is_null($path));
    
        return $pdfList;
    }

    /**
     * Return stats from the API server
     *
     * @param $type string - The stats type (or context)
     * @return bool|mixed
     */
    public function getApiStats( $type ){

        $path = '/v1/stats/' . $type;

        $response = $this->client()->get( $this->apiBaseUri . $path );

        $code = $response->getStatusCode();

        if ($code != 200) {
            return $this->log($response, false);
        }

        return $response->json();

    }
    
    /**
     * Return user stats from the auth server
     *
     * @return bool|mixed
     */
    public function getAuthStats( ){

        $response = $this->client()->get( $this->authBaseUri . '/v1/stats' );
    
        $code = $response->getStatusCode();
        
        if ($code != 200) {
            return $this->log($response, false);
        }
    
        return $response->json();
    
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
     * @param string $email
     */
    public function setEmail($email)
    {
        die('Deprecated in v4');

        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        die('Deprecated in v4');

        if (is_null($this->email) && !is_null($this->token)) {
            $this->setEmailAndUserIdFromToken();
        }

        return $this->email;
    }
    
    /**
     * Returns metadata of for give LPA id. 
     *
     * @param string $lpaId
     * @return array
     */
    public function getMetaData($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'metadata', $this);
        return $helper->getRawJson();
    }
    
    /**
     * Sets metadata for give LPA id
     *
     * @param string $lpaId
     * @param array $metadata
     * @return boolean
     */
    /*
    public function setMetaData($lpaId, $metadata)
    {
        $helper = new ApplicationResourceService($lpaId, 'metadata', $this);
        return $helper->setResource(json_encode($metadata));
    }
    */
    
    /**
     * Deletes metadata for given LPA id
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteMetaData($lpaId)
    {
        $helper = new ApplicationResourceService($lpaId, 'metadata', $this);
        return $helper->deleteResource();
    }
    
    

    /**
     * Log the response of the API call and set some internal member vars
     * If content body is JSON, convert it to an array
     * 
     * @param Response $response
     * @param bool $isSuccess
     * @return boolean
     * 
     * @todo - External logging
     */
    public function log(Response $response, $isSuccess=true)
    {
        $this->setLastStatusCode($response->getStatusCode());

        $responseBody = (string)$response->getBody();
        $jsonDecoded = json_decode($responseBody, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            $this->setLastContent($jsonDecoded);
        } else {
            $this->setLastContent($responseBody);
        }

        // @todo - Log properly
        if (!$isSuccess) { 
        }
        
        $this->setIsError(!$isSuccess);

        return $isSuccess;
    }
}
