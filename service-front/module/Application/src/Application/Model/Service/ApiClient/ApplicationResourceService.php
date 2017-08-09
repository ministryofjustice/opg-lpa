<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\Response;
use Exception;

class ApplicationResourceService
{
    private $apiClient;
    private $endpoint;
    private $resourceType;
    private $isSuccess = false;

    /**
     * @param $lpaId
     * @param $resourceType
     * @param Client $apiClient
     */
    public function __construct($lpaId, $resourceType, Client $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->resourceType = $resourceType;
        $this->endpoint = $apiClient->getApiBaseUri() . '/v1/users/' . $this->apiClient->getUserId() . '/applications/' . $lpaId . '/' . $resourceType;
    }

    /**
     * Return the API response for getting the resource of the given type
     *
     * If property not yet set, return null
     * If error, return false
     */
    public function getResource()
    {
        $response = $this->httpClient()->get($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            return $this->log($response, false);
        }

        $this->isSuccess = true;

        return $response;
    }

    /**
     * Get list of resources for the current user
     * Combine pages, if necessary
     */
    public function getResourceList($entityClass)
    {
        $resourceList = array();

        do {
            $response = $this->httpClient()->get($this->endpoint);

            $json = $response->json();

            if (!isset($json['_links']) || !isset($json['count'])) {
                return $this->log($response, false);
            }

            if ($json['count'] == 0) {
                return [];
            }

            if (!isset($json['_embedded'][$this->resourceType])) {
                return $this->log($response, false);
            }

            foreach ($json['_embedded'][$this->resourceType] as $singleResourceJson) {
                $concreteClass = $this->getConcreteClassIfAbstract($entityClass, $singleResourceJson);

                $resourceList[] = new $concreteClass($singleResourceJson);
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
     * Determine the concrete class for the abstract base
     *
     * @param string $entityClass
     * @param string $json
     * @return string
     * @throws Exception
     */
    private function getConcreteClassIfAbstract($entityClass, $json)
    {
        if ($entityClass == '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney') {
            switch ($json['type']) {
                case 'human':
                    return '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human';
                case 'trust':
                    return '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation';
                default:
                    throw new Exception('Invalid attorney type: ' . $json['type']);
            }
        }

        return $entityClass;
    }

    /**
     * Return the processed response for setting a single-valued entity
     * (e.g., type or preferences, which are both simply strings rather than classes)
     *
     * @param $key The JSON key of the value being retrieved
     * If property not yet set, return null
     * If error, return false
     * Else, return the value
     * @return boolean|null|mixed
     */
    public function getSingleValueResource($key)
    {
        $response = $this->httpClient()->get($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            return $this->log($response, false);
        }

        $json = $response->json();

        if (!isset($json[$key])) {
            return $this->log($response, false);
        }

        return $json[$key];
    }

    /**
     * Return the json response for an endpoint
     *
     * @param $key The JSON key of the value being retrieved
     * @return boolean|null|mixed
     */
    public function getRawJson()
    {
        $response = $this->httpClient()->get($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            return $this->log($response, false);
        }

        return $response->json();
    }

    /**
     * Return the processed response for setting a data model entity
     * (e.g., type or preferences, which are both simply strings rather than classes)
     *
     * @param string $entityClass The class of the data model entity to populate with the JSON from the API response
     * @param $index number in series, if applicable
     * If property not yet set, return null
     * If error, return false
     * Else, return the value
     * @return boolean|null|mixed
     */
    public function getEntityResource($entityClass, $index = null)
    {
        $response = $this->httpClient()->get($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            return $this->log($response, false);
        }

        $json = $response->json();

        $concreteClass = $this->getConcreteClassIfAbstract(
            $entityClass,
            $json
        );

        $entity = new $concreteClass($json);

        return $entity;
    }

    /**
     * Set the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function setResource($jsonBody, $index = null)
    {
        $response = $this->httpClient()->put($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (($response->getStatusCode() != 200) && ($response->getStatusCode() != 204)) {
            return $this->log($response, false);
        }

        $this->isSuccess = true;

        return true;
    }

    /**
     * Patch the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function updateResource($jsonBody, $index = null)
    {
        $response = $this->httpClient()->patch($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 200) {
            return $this->log($response, false);
        }

        $this->isSuccess = true;
        return true;
    }

    /**
     * Add data for the given resource. i.e. POST
     *
     * @param string $jsonBody
     * @return boolean
     */
    public function addResource($jsonBody)
    {
        $response = $this->httpClient()->post($this->endpoint, [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 201) {
            return $this->log($response, false);
        }

        $this->isSuccess = true;

        return true;
    }

    /**
     * Delete the resource type from the LPA. i.e. DELETE
     *
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function deleteResource($index = null)
    {
        $response = $this->httpClient()->delete($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 204) {
            return $this->log($response, false);
        }

        $this->isSuccess = true;

        return true;
    }

    /**
     * Call the client's log method and set our success status
     *
     * @param Response $response
     * @param boolean $isSuccess
     * @return bool
     */
    public function log($response, $isSuccess = true)
    {
        $this->apiClient->log($response, $isSuccess);
        $this->isSuccess = $isSuccess;

        return $isSuccess;
    }

    /**
     * Was the previous API call a success?
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * Returns the GuzzleClient.
     *
     * If an authentication token is available it will be preset in the HTTP header.
     *
     * @return GuzzleClient
     */
    private function httpClient()
    {
        if (!isset($this->guzzleClient)) {
            $this->guzzleClient = new GuzzleClient();
            $this->guzzleClient->setDefaultOption('exceptions', false);
            $this->guzzleClient->setDefaultOption('allow_redirects', false);
        }

        if ($this->apiClient->getToken() != null) {
            $this->guzzleClient->setDefaultOption('headers/Token', $this->apiClient->getToken());
        }

        return $this->guzzleClient;
    }
}
