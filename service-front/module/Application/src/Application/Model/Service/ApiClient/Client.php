<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

//  TODO - Use statements below to be removed...
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\Response;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;

class Client
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var string
     */
    private $token;

    /**
     * Client constructor
     *
     * @param HttpClientInterface $httpClient
     * @param $apiBaseUri
     * @param $token
     */
    public function __construct(HttpClientInterface $httpClient, $apiBaseUri, $token)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
        $this->token = $token;
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'LPA-FRONT'
        ];

        if (!is_null($this->token)) {
            $headers['Token'] = $this->token;
        }

        return $headers;
    }

    /**
     * Performs a GET against the API
     *
     * @param $path
     * @param array $query
     * @return ResponseInterface
     */
    public function httpGet($path, array $query = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, urlencode($value));
        }

        $request = new Request('GET', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 404])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Performs a POST against the API
     *
     * @param $path
     * @param array $payload
     * @return ResponseInterface
     */
    public function httpPost($path, array $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $body = (!empty($payload) ? json_encode($payload) : null);
        $request = new Request('POST', $url, $this->buildHeaders(), $body);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 201, 204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Performs a DELETE against the API
     *
     * @param $path
     * @return ResponseInterface
     */
    public function httpDelete($path)
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Performs a PATCH against the API
     *
     * @param $path
     * @param array $payload
     * @return ResponseInterface
     */
    public function httpPatch($path, array $payload = [])
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() != 200) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    /**
     * Called with a response from the API when the response code was unsuccessful. i.e. not 20X.
     *
     * @param ResponseInterface $response
     *
     * @return Exception\ResponseException
     */
    protected function createErrorException(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ResponseException($message, $response->getStatusCode(), $response);
    }



//  TODO - Refactor the functions below out into service...
    /**
     *
     * @var GuzzleClient
     */
    private $guzzleClient;

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

        if (!is_null($this->token)) {
            $this->guzzleClient->setDefaultOption('headers/Token', $this->token);
        }

        return $this->guzzleClient;
    }

    /**
     * Get the base URI with the user ID, LPA ID and (if applicable) the resource type suffix
     *
     * @param $userId
     * @param $lpaId
     * @param null $resourceType
     * @return string
     */
    private function getApiBaseUriForLpa($userId, $lpaId, $resourceType = null)
    {
        $uri = $this->getApiBaseUriForUser($userId) . '/applications/' . $lpaId;

        if (is_string($resourceType)) {
            $uri .= '/' . $resourceType;
        }

        return $uri;
    }

    /**
     * Get the base URI with the user ID
     *
     * @param $userId
     * @return string
     */
    private function getApiBaseUriForUser($userId)
    {
        return $this->apiBaseUri . '/v1/users/' . $userId;
    }

    /**
     * Delete all the LPAs from an account
     *
     * @param $userId
     * @return bool
     */
    public function deleteAllLpas($userId)
    {
        $response = $this->getClient()->delete($this->getApiBaseUriForUser($userId), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Token' => $this->token,
            ],
            'body' => '{}',
        ]);

        if ($response->getStatusCode() != 204) {
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
        $response = $this->getClient()->put($this->getApiBaseUriForUser($user->getId()), [
            'body' => $user->toJson(),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        return true;
    }

    /**
     * Get the user's about me details
     *
     * TODO - Absorbs this into the client details service
     *
     * @param $userId
     * @return bool|User
     */
    public function getAboutMe($userId)
    {
        $path = $this->getApiBaseUriForUser($userId);

        $response = $this->getClient()->get($path, [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        return new User($response->json());
    }

    /**
     * Set the LPA type
     *
     * @param $userId
     * @param $lpaId
     * @param $repeatCaseNumber
     * @return bool
     */
    public function setRepeatCaseNumber($userId, $lpaId, $repeatCaseNumber)
    {
        return $this->setResource($userId, $lpaId, 'repeat-case-number', json_encode(['repeatCaseNumber' => $repeatCaseNumber]));
    }

    /**
     * Delete the type from the LPA
     *
     * @param $userId
     * @param $lpaId
     * @return bool
     */
    public function deleteRepeatCaseNumber($userId, $lpaId)
    {
        return $this->deleteResource($userId, $lpaId, 'repeat-case-number');
    }

    /**
     * Set the LPA type
     *
     * @param $userId
     * @param $lpaId
     * @param $lpaType
     * @return bool
     */
    public function setType($userId, $lpaId, $lpaType)
    {
        return $this->setResource($userId, $lpaId, 'type', json_encode(['type' => $lpaType]));
    }

    /**
     * Set the LPA instructions
     *
     * @param $userId
     * @param $lpaId
     * @param $lpaInstructions
     * @return bool
     */
    public function setInstructions($userId, $lpaId, $lpaInstructions)
    {
        return $this->setResource($userId, $lpaId, 'instruction', json_encode(['instruction' => $lpaInstructions]));
    }

    /**
     * Set the LPA preferences
     *
     * @param $userId
     * @param $lpaId
     * @param $preferences
     * @return bool
     */
    public function setPreferences($userId, $lpaId, $preferences)
    {
        return $this->setResource($userId, $lpaId, 'preference', json_encode(['preference' => $preferences]));
    }

    /**
     * Set the primary attorney decisions
     *
     * @param $userId
     * @param $lpaId
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     * @return bool
     */
    public function setPrimaryAttorneyDecisions($userId, $lpaId, PrimaryAttorneyDecisions $primaryAttorneyDecisions)
    {
        return $this->setResource($userId, $lpaId, 'primary-attorney-decisions', $primaryAttorneyDecisions->toJson());
    }

    /**
     * Set the replacement attorney decisions
     *
     * @param $userId
     * @param $lpaId
     * @param ReplacementAttorneyDecisions $replacementAttorneyDecisions
     * @return bool
     */
    public function setReplacementAttorneyDecisions($userId, $lpaId, ReplacementAttorneyDecisions $replacementAttorneyDecisions)
    {
        return $this->setResource($userId, $lpaId, 'replacement-attorney-decisions', $replacementAttorneyDecisions->toJson());
    }

    /**
     * Set the donor
     *
     * @param $userId
     * @param $lpaId
     * @param Donor $donor
     * @return bool
     */
    public function setDonor($userId, $lpaId, Donor $donor)
    {
        return $this->setResource($userId, $lpaId, 'donor', $donor->toJson());
    }

    /**
     * Set the correspondent
     *
     * @param $userId
     * @param $lpaId
     * @param Correspondence $correspondent
     * @return bool
     */
    public function setCorrespondent($userId, $lpaId, Correspondence $correspondent)
    {
        return $this->setResource($userId, $lpaId, 'correspondent', $correspondent->toJson());
    }

    /**
     * Delete the correspondent
     *
     * @param $userId
     * @param $lpaId
     * @return bool
     */
    public function deleteCorrespondent($userId, $lpaId)
    {
        return $this->deleteResource($userId, $lpaId, 'correspondent');
    }

    /**
     * Set the payment information
     *
     * @param $userId
     * @param $lpaId
     * @param Payment $payment
     * @return bool
     */
    public function setPayment($userId, $lpaId, Payment $payment)
    {
        return $this->setResource($userId, $lpaId, 'payment', $payment->toJson());
    }

    /**
     * Sets the person/organisation of who completed the application
     *
     * @param $userId
     * @param $lpaId
     * @param WhoAreYou $whoAreYou
     * @return bool
     */
    public function setWhoAreYou($userId, $lpaId, WhoAreYou $whoAreYou)
    {
        return $this->addResource($userId, $lpaId, 'who-are-you', $whoAreYou->toJson());
    }

    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     *
     * @param $userId
     * @param $lpaId
     * @return bool
     */
    public function lockLpa($userId, $lpaId)
    {
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, 'lock');

        $response = $this->getClient()->post($endpoint);

        if ($response->getStatusCode() != 201) {
            return false;
        }

        $json = $response->json();

        if (!isset($json['locked'])) {
            return false;
        }

        return $json['locked'];
    }

    /**
     * Returns the id of the seed LPA document and the list of actors
     *
     * @param $userId
     * @param $lpaId
     * @return mixed
     */
    public function getSeedDetails($userId, $lpaId)
    {
        return $this->getResource($userId, $lpaId, 'seed', true);
    }

    /**
     * Sets the id of the seed LPA
     *
     * @param $userId
     * @param $lpaId
     * @param $seedId
     * @return bool
     */
    public function setSeed($userId, $lpaId, $seedId)
    {
        return $this->setResource($userId, $lpaId, 'seed', json_encode(['seed' => $seedId]));
    }

    /**
     * Adds a new notified person
     *
     * @param $userId
     * @param $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @return bool
     */
    public function addNotifiedPerson($userId, $lpaId, NotifiedPerson $notifiedPerson)
    {
        return $this->addResource($userId, $lpaId, 'notified-people', $notifiedPerson->toJson());
    }

    /**
     * Sets the notified person for the given notified person id
     *
     * @param $userId
     * @param $lpaId
     * @param NotifiedPerson $notifiedPerson
     * @param $notifiedPersonId
     * @return bool
     */
    public function setNotifiedPerson($userId, $lpaId, NotifiedPerson $notifiedPerson, $notifiedPersonId)
    {
        return $this->setResource($userId, $lpaId, 'notified-people', $notifiedPerson->toJson(), $notifiedPersonId);
    }

    /**
     * Deletes the person to notify for the given notified person id
     *
     * @param string $lpaId
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function deleteNotifiedPerson($userId, $lpaId, $notifiedPersonId)
    {
        return $this->deleteResource($userId, $lpaId, 'notified-people', $notifiedPersonId);
    }

    /**
     * Returns a list of all currently set primary attorneys
     *
     * @param $userId
     * @param $lpaId
     * @return array|bool
     */
    public function getPrimaryAttorneys($userId, $lpaId)
    {
        return $this->getResourceList($userId, $lpaId, 'primary-attorneys', '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney');
    }

    /**
     * Adds a new primary attorney
     *
     * @param $userId
     * @param $lpaId
     * @param AbstractAttorney $primaryAttorney
     * @return bool
     */
    public function addPrimaryAttorney($userId, $lpaId, AbstractAttorney $primaryAttorney)
    {
        return $this->addResource($userId, $lpaId, 'primary-attorneys', $primaryAttorney->toJson());
    }

    /**
     * Sets the primary attorney for the given primary attorney id
     *
     * @param $userId
     * @param $lpaId
     * @param AbstractAttorney $primaryAttorney
     * @param $primaryAttorneyId
     * @return bool
     */
    public function setPrimaryAttorney($userId, $lpaId, AbstractAttorney $primaryAttorney, $primaryAttorneyId)
    {
        return $this->setResource($userId, $lpaId, 'primary-attorneys', $primaryAttorney->toJson(), $primaryAttorneyId);
    }

    /**
     * Deletes the person to notify for the given primary attorney id
     *
     * @param $userId
     * @param $lpaId
     * @param $primaryAttorneyId
     * @return bool
     */
    public function deletePrimaryAttorney($userId, $lpaId, $primaryAttorneyId)
    {
        return $this->deleteResource($userId, $lpaId, 'primary-attorneys', $primaryAttorneyId);
    }

    /**
     * Adds a new replacement attorney
     *
     * @param $userId
     * @param $lpaId
     * @param AbstractAttorney $replacementAttorney
     * @return bool
     */
    public function addReplacementAttorney($userId, $lpaId, AbstractAttorney $replacementAttorney)
    {
        return $this->addResource($userId, $lpaId, 'replacement-attorneys', $replacementAttorney->toJson());
    }

    /**
     * Sets the replacement attorney for the given replacement attorney id
     *
     * @param $userId
     * @param $lpaId
     * @param AbstractAttorney $replacementAttorney
     * @param $replacementAttorneyId
     * @return bool
     */
    public function setReplacementAttorney($userId, $lpaId, AbstractAttorney $replacementAttorney, $replacementAttorneyId)
    {
        return $this->setResource($userId, $lpaId, 'replacement-attorneys', $replacementAttorney->toJson(), $replacementAttorneyId);
    }

    /**
     * Deletes the person to notify for the given replacement attorney id
     *
     * @param $userId
     * @param $lpaId
     * @param $replacementAttorneyId
     * @return bool
     */
    public function deleteReplacementAttorney($userId, $lpaId, $replacementAttorneyId)
    {
        return $this->deleteResource($userId, $lpaId, 'replacement-attorneys', $replacementAttorneyId);
    }

    /**
     * Set the certificate provider
     *
     * @param $userId
     * @param $lpaId
     * @param $certificateProvider
     * @return bool
     */
    public function setCertificateProvider($userId, $lpaId, $certificateProvider)
    {
        return $this->setResource($userId, $lpaId, 'certificate-provider', $certificateProvider->toJson());
    }

    /**
     * Delete the certificate provider
     *
     * @param $userId
     * @param $lpaId
     * @return bool
     */
    public function deleteCertificateProvider($userId, $lpaId)
    {
        return $this->deleteResource($userId, $lpaId, 'certificate-provider');
    }

    /**
     * Set Who Is Registering
     *
     * @param $userId
     * @param $lpaId
     * @param $who
     * @return bool
     */
    public function setWhoIsRegistering($userId, $lpaId, $who)
    {
        return $this->setResource($userId, $lpaId, 'who-is-registering', json_encode(['who' => $who]));
    }

    /**
     * Returns the PDF details for the specified PDF type
     *
     * @param $userId
     * @param $lpaId
     * @param $pdfName
     * @return mixed
     */
    public function getPdfDetails($userId, $lpaId, $pdfName)
    {
        $resource = $this->getResource($userId, $lpaId, 'pdfs/' . $pdfName);

        return json_decode($resource->getBody(), true);
    }

    /**
     * Returns the PDF body for the specified PDF type
     *
     * @param $userId
     * @param $lpaId
     * @param $pdfName
     * @return bool|\GuzzleHttp\Stream\StreamInterface|null
     */
    public function getPdf($userId, $lpaId, $pdfName)
    {
        $resourceType = 'pdfs/' . $pdfName . '.pdf';
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

        $response = $this->getClient()->get($endpoint);

        $code = $response->getStatusCode();

        if ($code != 200) {
            return false;
        }

        return $response->getBody();
    }






    /**
     * Return the API response for getting the resource of the given type
     *
     * If property not yet set, return null
     * If error, return false
     *
     * @param $userId
     * @param $lpaId
     * @param $resourceType
     * @param bool $asRawJson
     * @return mixed
     */
    private function getResource($userId, $lpaId, $resourceType, $asRawJson = false)
    {
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

        $response = $this->getClient()->get($endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
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
     *
     * @param $userId
     * @param $lpaId
     * @param $resourceType
     * @param $entityClass
     * @return array|bool
     */
    private function getResourceList($userId, $lpaId, $resourceType, $entityClass)
    {
        $resourceList = array();

        do {
            $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

            $response = $this->getClient()->get($endpoint);

            $json = $response->json();

            if (!isset($json['_links']) || !isset($json['count'])) {
                return false;
            }

            if ($json['count'] == 0) {
                return [];
            }

            if (!isset($json['_embedded'][$resourceType])) {
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
     * @param $userId
     * @param $lpaId
     * @param $resourceType
     * @param $jsonBody
     * @param null $index
     * @return bool
     */
    private function setResource($userId, $lpaId, $resourceType, $jsonBody, $index = null)
    {
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

        $response = $this->getClient()->put($endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (($response->getStatusCode() != 200) && ($response->getStatusCode() != 204)) {
            return false;
        }

        return true;
    }

    /**
     * Add data for the given resource. i.e. POST
     *
     * @param $userId
     * @param $lpaId
     * @param $resourceType
     * @param $jsonBody
     * @return bool
     */
    private function addResource($userId, $lpaId, $resourceType, $jsonBody)
    {
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

        $response = $this->getClient()->post($endpoint, [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 201) {
            return false;
        }

        return true;
    }

    /**
     * Delete the resource type from the LPA. i.e. DELETE
     *
     * @param $userId
     * @param $lpaId
     * @param $resourceType
     * @param null $index
     * @return bool
     */
    private function deleteResource($userId, $lpaId, $resourceType, $index = null)
    {
        $endpoint = $this->getApiBaseUriForLpa($userId, $lpaId, $resourceType);

        $response = $this->getClient()->delete($endpoint . (!is_null($index) ? '/' . $index : ''), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}'
        ]);

        if ($response->getStatusCode() != 204) {
            return false;
        }

        return true;
    }

}
