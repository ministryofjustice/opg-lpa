<?php
namespace Opg\Lpa\Api\Client;

use Opg\Lpa\Api\Client\Common\Guzzle\Client as GuzzleClient;
use Opg\Lpa\Api\Client\Response\AuthResponse;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Api\Client\Response\LpaStatusResponse;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;

class Client
{

    const PATH_API = 'http://localhost:8083';
    const PATH_AUTH = 'http://auth.local';
    const PATH_PDF = 'http://pdf.local';

    /**
     * The API auth token
     * 
     * @var string
     */
    private $token;

    /**
     * 
     * @var GuzzleClient
     */
    private $guzzleClient;
    
    
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
            return $authResponse->setErrorDescription( "Authentication failed" );
        }

        $authResponse->exchangeJson( $response->getBody() );

        $this->setToken( $authResponse->getToken() );

        //-------------------------
        // Get the user's details

        $response = $this->client()->get( self::PATH_AUTH . '/tokeninfo', [
            'query' => [ 'access_token' => $this->getToken() ]
        ]);

        if( $response->getStatusCode() != 200 ){
            return $authResponse->setErrorDescription( "Authentication failed" );
        }

        $response = $response->json();

        if( !isset($response['user_id']) ){
            return $authResponse->setErrorDescription( "Authentication failed" );
        }

        $authResponse->setUserId( $response['user_id'] );

        //---

        return $authResponse;
    }
    
    /**
     * Destroy the authentication token
     */
    public function destroyToken()
    {
        return $this;
    }
    
    /**
     * Register a new account
     * 
     * @param array $params
     * @return Client $this
     */
    public function registerAccount(array $params)
    {
        return $this;
    }
    
    /**
     * Delete an account
     *
     * @return Client $this
     */
    public function deleteAccount($email)
    {
        return $this;
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
     * Create an empty LPA
     * 
     * @return string
     */
    public function createLpa()
    {
        return 'LPAID';
    }
    
    /**
     * Return the LPAs for this User
     * 
     * @param \Opg\Lpa\DataModel\User\User $user
     * @return array of LPAs:
     */
    public function fetchLpas(User $user)
    {
        return [];    
    }
    
    /**
     * Delete an LPA
     * 
     * @param \Opg\Lpa\DataModel\Lpa\Lpa $lpa
     * @return boolean
     */
    public function deleteLpa($lpaId)
    {
        return false;
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
     * @param string $lpaId
     * @return number Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_PF | LPA_TYPE_HW
     */
    public function getType($lpaId)
    {
        return Document::LPA_TYPE_HW;    
    }
    
    /**
     * Set the LPA type
     * 
     * @param string $lpaId
     * @param number $lpaTypeId
     * @return boolean
     */
    public function setType($lpaId, $lpaTypeId)
    {
        return false;
    }
    
    /**
     * Delete the type from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteType($lpaId)
    {
        return true;
    }
    
    /**
     * Get the instructions associated with the LPA
     * 
     * @param string $lpaId
     * @return string
     */
    public function getInstructions($lpaId)
    {
        return '';
    }
    
    /**
     * Set the instructions associated with the LPA
     * 
     * @param string $lpaId
     * @param string $instructions
     * @return boolean
     */
    public function setInstructions($lpaId, $instructions)
    {
        return true;
    }
    
    /**
     * Delete the instructions associated with the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteInstructions($lpaId)
    {
        return true;
    }
    
    /**
     * Get the preferences associated with the LPA
     *
     * @param string $lpaId
     * @return string
     */
    public function getPreferences($lpaId)
    {
        return '';
    }
    
    /**
     * Set the preferences associated with the LPA
     *
     * @param string $lpaId
     * @param string $preferences
     * @return boolean
     */
    public function setPreferences($lpaId, $preferences)
    {
        return true;
    }
    
    /**
     * Delete the preferences associated with the LPA
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deletePreferences($lpaId)
    {
        return true;
    }

    /**
     * Get the primary attorney decisions
     *
     * @param string $lpaId
     * @return PrimaryAttorneyDecisions
     */
    public function getPrimaryAttorneyDecisions($lpaId)
    {
        return new PrimaryAttorneyDecisions();
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
        $primaryAttorneyDecisions
    )
    {
        return true;
    }
    
    /**
     * Delete the primary attorney decisions
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deletePrimaryAttorneyDecisions($lpaId)
    {
        return true;
    }
    
    /**
     * Get the replacement attorney decisions
     *
     * @param string $lpaId
     * @return ReplacementAttorneyDecisions
     */
    public function getReplacementAttorneyDecisions($lpaId)
    {
        return new replacementAttorneyDecisions();
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
        $replacementAttorneyDecisions
    )
    {
        return true;
    }
    
    /**
     * Delete the replacement attorney decisions
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteReplacementAttorneyDecisions($lpaId)
    {
        return true;
    }
    
    /**
     * Get the donor
     *
     * @param string $lpaId
     * @return Donor
     */
    public function getDonor($lpaId)
    {
        return new Donor();
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
        $donor
    )
    {
        return true;
    }
    
    /**
     * Delete the donor
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteDonor($lpaId)
    {
        return true;
    }
    
    /**
     * Get the correspondent
     *
     * @param string $lpaId
     * @return Correspondence
     */
    public function getCorrespondent($lpaId)
    {
        return new Correspondence();
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
        return true;
    }
    
    /**
     * Delete the correspondent
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCorrespondent($lpaId)
    {
        return true;
    }
    
    /**
     * Get the payment information
     *
     * @param string $lpaId
     * @return Correspondence
     */
    public function getPayment($lpaId)
    {
        return new Correspondence();
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
        return true;
    }
    
    /**
     * Delete the payment information
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deletePayment($lpaId)
    {
        return true;
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
        return true;    
    }
    
    /**
     * Returns whether the LPA is currently locked
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function isLpaLocked($lpaId)
    {
        return true;
    }
    
    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function lockLpa($lpaId)
    {
        return true;
    }
    
    /**
     * Returns the id of the seed LPA document and the list of actors
     * 
     * @param string $lpaId
     * @return array [id=>,donor=>,attorneys=>[],certificateProviders=>,notifiedPersons=>[],correspondent=>]
     */
    public function getSeedDetails($lpaId)
    {
        return [];
    }
    
    /**
     * Sets the id of the seed LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function setSeed($lpaId)
    {
        return true;
    }
 
    /**
     * Deletes the seed reference from the LPA
     * 
     * @param string $lpaId
     * @return boolean
     */
    public function deleteSeed($lpaId)
    {
        return true;
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
        return [];
    }
    
    /**
     * Adds a new notified person
     *
     * @param string $lpaId
     * @return boolean
     */
    public function addNotifiedPerson($lpaId)
    {
        return true;
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
        return null;
    }
    
    /**
     * Sets the notified person for the given notified person id
     *
     * @param string $lpaId
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function setNotifiedPerson(
        $lpaId,
        $notifiedPersonId
    )
    {
        return true;
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
        return true;
    }
    
    /**
     * Get the certificate provider
     *
     * @param string $lpaId
     * @return CertificateProvider
     */
    public function getCertificateProvider($lpaId)
    {
        return new CertificateProvider();
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
        return true;
    }
    
    /**
     * Delete the certificate provider
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCertificateProvider($lpaId)
    {
        return true;
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
}
