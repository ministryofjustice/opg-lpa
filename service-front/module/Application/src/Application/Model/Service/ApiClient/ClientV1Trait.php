<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\Response;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;

trait ClientV1Trait
{
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
     * Returns the GuzzleClient.
     *
     * If a authentication token is available it will be preset in the HTTP header.
     *
     * @return GuzzleClient
     */
    private function getClient()
    {
        if (!isset($this->guzzleClient)) {
            $this->guzzleClient = new GuzzleClient();
            $this->guzzleClient->setDefaultOption('exceptions', false);
            $this->guzzleClient->setDefaultOption('allow_redirects', false);
        }

        if ($this->getToken() != null) {
            $this->guzzleClient->setDefaultOption('headers/Token', $this->getToken());
        }

        return $this->guzzleClient;
    }

    /**
     * @return number
     */
    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    /**
     * @return string
     */
    public function getLastContent()
    {
        return $this->lastContent;
    }

    /**
     * Get the base URI with the user ID
     *
     * @return string
     */
    private function getApiBaseUriForUser()
    {
        return $this->apiBaseUri . '/v1/users/' . $this->getUserId();
    }

    /**
     * Get the base URI with the user ID, LPA ID and (if applicable) the resource type suffix
     *
     * @param $lpaId
     * @param $resourceType
     * @return string
     */
    private function getApiBaseUriForLpa($lpaId, $resourceType = null)
    {
        $uri = $this->getApiBaseUriForUser() . '/applications/' . $lpaId;

        if (is_string($resourceType)) {
            $uri .= '/' . $resourceType;
        }

        return $uri;
    }

    /**
     * Delete all the LPAs from an account
     *
     * @return bool
     */
    public function deleteAllLpas()
    {
        $response = $this->getClient()->delete($this->getApiBaseUriForUser(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Token' => $this->getToken(),
            ],
            'body' => '{}',
        ]);

        if ($response->getStatusCode() != 204) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Set user's about me details
     *
     * @param User $user
     * @return bool
     */
    public function setAboutMe(User $user)
    {
        $response = $this->getClient()->put($this->getApiBaseUriForUser(), [
            'body' => $user->toJson(),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Get the user's about me details
     *
     * @return mixed
     */
    public function getAboutMe()
    {
        $response = $this->getClient()->get($this->getApiBaseUriForUser(), [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return new User($response->json());
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
        return $this->setResource($lpaId, 'repeat-case-number', json_encode(['repeatCaseNumber' => $repeatCaseNumber]));
    }

    /**
     * Delete the type from the LPA
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteRepeatCaseNumber($lpaId)
    {
        return $this->deleteResource($lpaId, 'repeat-case-number');
    }

    /**
     * Set the LPA type
     *
     * @param string $lpaId
     * @param string $lpaType  - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * @return boolean
     */
    public function setType($lpaId, $lpaType)
    {
        return $this->setResource($lpaId, 'type', json_encode(['type' => $lpaType]));
    }

    /**
     * Set the LPA instructions
     *
     * @param string $lpaId
     * @param number $lpaInstructions - Document::LPA_TYPE_PF or Document::LPA_TYPE_HW
     * @return boolean
     */
    public function setInstructions($lpaId, $lpaInstructions)
    {
        return $this->setResource($lpaId, 'instruction', json_encode(['instruction' => $lpaInstructions]));
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
        return $this->setResource($lpaId, 'preference', json_encode(['preference' => $preferences]));
    }

    /**
     * Set the primary attorney decisions
     *
     * @param string $lpaId
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     * @return boolean
     */
    public function setPrimaryAttorneyDecisions($lpaId, PrimaryAttorneyDecisions $primaryAttorneyDecisions)
    {
        return $this->setResource($lpaId, 'primary-attorney-decisions', $primaryAttorneyDecisions->toJson());
    }

    /**
     * Set the replacement attorney decisions
     *
     * @param string $lpaId
     * @param ReplacementAttorneyDecisions $replacementAttorneyDecisions
     * @return boolean
     */
    public function setReplacementAttorneyDecisions($lpaId, ReplacementAttorneyDecisions $replacementAttorneyDecisions)
    {
        return $this->setResource($lpaId, 'replacement-attorney-decisions', $replacementAttorneyDecisions->toJson());
    }

    /**
     * Set the donor
     *
     * @param string $lpaId
     * @param Donor $donor
     * @return boolean
     */
    public function setDonor($lpaId, Donor $donor)
    {
        return $this->setResource($lpaId, 'donor', $donor->toJson());
    }

    /**
     * Set the correspondent
     *
     * @param string $lpaId
     * @param Correspondence $correspondent
     * @return boolean
     */
    public function setCorrespondent($lpaId, Correspondence $correspondent)
    {
        return $this->setResource($lpaId, 'correspondent', $correspondent->toJson());
    }

    /**
     * Delete the correspondent
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCorrespondent($lpaId)
    {
        return $this->deleteResource($lpaId, 'correspondent');
    }

    /**
     * Set the payment information
     *
     * @param string $lpaId
     * @param Payment $payment
     * @return boolean
     */
    public function setPayment($lpaId, Payment $payment)
    {
        return $this->setResource($lpaId, 'payment', $payment->toJson());
    }

    /**
     * Sets the person/organisation of who completed the application
     *
     * @param unknown $lpaId
     * @param WhoAreYou $whoAreYou
     * @return boolean
     */
    public function setWhoAreYou($lpaId, WhoAreYou $whoAreYou)
    {
        return $this->addResource($lpaId, 'who-are-you', $whoAreYou->toJson());
    }

    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     *
     * @param string $lpaId
     * @return boolean
     */
    public function lockLpa($lpaId)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, 'lock');

        $response = $this->getClient()->post($endpoint);

        if ($response->getStatusCode() != 201) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        $json = $response->json();

        if (!isset($json['locked'])) {
            $this->recordErrorResponseDetails($response);
            return false;
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
        return $this->getResource($lpaId, 'seed', true);
    }

    /**
     * Sets the id of the seed LPA
     *
     * @param string $lpaId
     * @return boolean
     */
    public function setSeed($lpaId, $seedId)
    {
        return $this->setResource($lpaId, 'seed', json_encode(['seed' => $seedId]));
    }

    /**
     * Adds a new notified person
     *
     * @param string $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @return boolean
     */
    public function addNotifiedPerson($lpaId, NotifiedPerson $notifiedPerson)
    {
        return $this->addResource($lpaId, 'notified-people', $notifiedPerson->toJson());
    }

    /**
     * Sets the notified person for the given notified person id
     *
     * @param string $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function setNotifiedPerson($lpaId, NotifiedPerson $notifiedPerson, $notifiedPersonId)
    {
        return $this->setResource($lpaId, 'notified-people', $notifiedPerson->toJson(), $notifiedPersonId);
    }

    /**
     * Deletes the person to notify for the given notified person id
     *
     * @param string $lpaId
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function deleteNotifiedPerson($lpaId, $notifiedPersonId)
    {
        return $this->deleteResource($lpaId, 'notified-people', $notifiedPersonId);
    }

    /**
     * Returns a list of all currently set primary attorneys
     *
     * @param string $lpaId
     * @return array
     */
    public function getPrimaryAttorneys($lpaId)
    {
        return $this->getResourceList($lpaId, 'primary-attorneys', '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney');
    }

    /**
     * Adds a new primary attorney
     *
     * @param string $lpaId
     * @param AbstractAttorney $primaryAttorney
     * @return boolean
     */
    public function addPrimaryAttorney($lpaId, AbstractAttorney $primaryAttorney)
    {
        return $this->addResource($lpaId, 'primary-attorneys', $primaryAttorney->toJson());
    }

    /**
     * Sets the primary attorney for the given primary attorney id
     *
     * @param string $lpaId
     * @param AbstractAttorney $primaryAttorney
     * @param string $primaryAttorneyId
     * @return boolean
     */
    public function setPrimaryAttorney($lpaId, AbstractAttorney $primaryAttorney, $primaryAttorneyId)
    {
        return $this->setResource($lpaId, 'primary-attorneys', $primaryAttorney->toJson(), $primaryAttorneyId);
    }

    /**
     * Deletes the person to notify for the given primary attorney id
     *
     * @param string $lpaId
     * @param string $primaryAttorneyId
     * @return boolean
     */
    public function deletePrimaryAttorney($lpaId, $primaryAttorneyId)
    {
        return $this->deleteResource($lpaId, 'primary-attorneys', $primaryAttorneyId);
    }

    /**
     * Adds a new replacement attorney
     *
     * @param string $lpaId
     * @param AbstractAttorney $replacementAttorney
     * @return boolean
     */
    public function addReplacementAttorney($lpaId, AbstractAttorney $replacementAttorney)
    {
        return $this->addResource($lpaId, 'replacement-attorneys', $replacementAttorney->toJson());
    }

    /**
     * Sets the replacement attorney for the given replacement attorney id
     *
     * @param string $lpaId
     * @param AbstractAttorney $replacementAttorney
     * @param string $replacementAttorneyId
     * @return boolean
     */
    public function setReplacementAttorney($lpaId, AbstractAttorney $replacementAttorney, $replacementAttorneyId)
    {
        return $this->setResource($lpaId, 'replacement-attorneys', $replacementAttorney->toJson(), $replacementAttorneyId);
    }

    /**
     * Deletes the person to notify for the given replacement attorney id
     *
     * @param string $lpaId
     * @param string $replacementAttorneyId
     * @return boolean
     */
    public function deleteReplacementAttorney($lpaId, $replacementAttorneyId)
    {
        return $this->deleteResource($lpaId, 'replacement-attorneys', $replacementAttorneyId);
    }

    /**
     * Set the certificate provider
     *
     * @param string $lpaId
     * @param CertificateProvider $certificateProvider
     * @return boolean
     */
    public function setCertificateProvider($lpaId, $certificateProvider)
    {
        return $this->setResource($lpaId, 'certificate-provider', $certificateProvider->toJson());
    }

    /**
     * Delete the certificate provider
     *
     * @param string $lpaId
     * @return boolean
     */
    public function deleteCertificateProvider($lpaId)
    {
        return $this->deleteResource($lpaId, 'certificate-provider');
    }

    /**
     * Set Who Is Registering
     *
     * @param string $lpaId
     * @param string|array $who
     * @return boolean
     */
    public function setWhoIsRegistering($lpaId, $who)
    {
        return $this->setResource($lpaId, 'who-is-registering', json_encode(['who' => $who]));
    }

    /**
     * Returns the PDF details for the specified PDF type
     *
     * @param string $lpaId
     * @param string $pdfName
     * @return mixed
     */
    public function getPdfDetails($lpaId, $pdfName)
    {
        $resource = $this->getResource($lpaId, 'pdfs/' . $pdfName);

        return json_decode($resource->getBody(), true);
    }

    /**
     * Returns the PDF body for the specified PDF type
     *
     * @param string $lpaId
     * @param string $pdfName
     * @return mixed
     */
    public function getPdf($lpaId, $pdfName)
    {
        $resourceType = 'pdfs/' . $pdfName . '.pdf';
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->get($endpoint);

        $code = $response->getStatusCode();

        if ($code != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return $response->getBody();
    }

    /**
     * Return stats from the API server
     *
     * @param $type string - The stats type (or context)
     * @return bool|mixed
     */
    public function getApiStats()
    {
        $response = $this->getClient()->get($this->apiBaseUri . '/v1/stats/all');

        $code = $response->getStatusCode();

        if ($code != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return $response->json();
    }

    /**
     * Return the API response for getting the resource of the given type
     *
     * If property not yet set, return null
     * If error, return false
     *
     * @param $lpaId
     * @param $resourceType
     * @param bool $asRawJson
     * @return mixed
     */
    private function getResource($lpaId, $resourceType, $asRawJson = false)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->get($endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        if ($response instanceof Response && $asRawJson === true) {
            $response = $response->json();
        }

        return $response;
    }

    /**
     * Get list of resources for the current user
     * Combine pages, if necessary
     */
    private function getResourceList($lpaId, $resourceType, $entityClass)
    {
        $resourceList = array();

        do {
            $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

            $response = $this->getClient()->get($endpoint);

            $json = $response->json();

            if (!isset($json['_links']) || !isset($json['count'])) {
                $this->recordErrorResponseDetails($response);
                return false;
            }

            if ($json['count'] == 0) {
                return [];
            }

            if (!isset($json['_embedded'][$resourceType])) {
                $this->recordErrorResponseDetails($response);
                return false;
            }

            foreach ($json['_embedded'][$resourceType] as $singleResourceJson) {
                //  If this is an attorney then determine which type
                if ($entityClass == '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney') {
                    switch ($singleResourceJson['type']) {
                        case 'human':
                            $entityClass = '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human';
                            break;
                        case 'trust':
                            $entityClass = '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation';
                            break;
                        default:
                            throw new Exception('Invalid attorney type: ' . $singleResourceJson['type']);
                    }
                }

                $resourceList[] = new $entityClass($singleResourceJson);
            }

            if (isset($json['_links']['next']['href'])) {
                $path = $json['_links']['next']['href'];
            } else {
                $path = null;
            }
        } while (!is_null($path));

        return $resourceList;
    }

    /**
     * Set the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    private function setResource($lpaId, $resourceType, $jsonBody, $index = null)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->put($endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (($response->getStatusCode() != 200) && ($response->getStatusCode() != 204)) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Patch the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    private function updateResource($lpaId, $resourceType, $jsonBody, $index = null)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->patch($endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Add data for the given resource. i.e. POST
     *
     * @param string $jsonBody
     * @return boolean
     */
    private function addResource($lpaId, $resourceType, $jsonBody)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->post($endpoint, [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 201) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Delete the resource type from the LPA. i.e. DELETE
     *
     * @param $index number in series, if applicable
     * @return boolean
     */
    private function deleteResource($lpaId, $resourceType, $index = null)
    {
        $endpoint = $this->getApiBaseUriForLpa($lpaId, $resourceType);

        $response = $this->getClient()->delete($endpoint . (!is_null($index) ? '/' . $index : ''), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}'
        ]);

        if ($response->getStatusCode() != 204) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return true;
    }

    /**
     * Log the response of the API call and set some internal member vars
     * If content body is JSON, convert it to an array
     *
     * @param Response $response
     * @return boolean
     */
    private function recordErrorResponseDetails(Response $response)
    {
        //  Note the last status code and response content
        $this->lastStatusCode = $response->getStatusCode();

        $responseBody = (string)$response->getBody();
        $this->lastContent = $responseBody;

        //  If the response body can be decoded to JSON then make that be the last response content
        $jsonDecoded = json_decode($responseBody, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            $this->lastContent = $jsonDecoded;
        }
    }
}
