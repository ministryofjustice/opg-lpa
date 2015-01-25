<?php
namespace Opg\Lpa\Api\Client\Service;


use Opg\Lpa\Api\Client\Common\Guzzle\Client as GuzzleClient;
use GuzzleHttp\Message\Response;
use Opg\Lpa\Api\Client\Client;

class ApplicationResourceService
{
    private $apiClient;
    private $endpoint;
    private $resourceType;
    private $isSuccess = false;
    
    /**
     * @param string $lpaId
     * @param string $resourceType
     * @param Client $client
     */
    public function __construct(
        $lpaId,
        $resourceType,
        Client $apiClient
    )
    {
        $this->apiClient = $apiClient;
        $this->resourceType = $resourceType;
        $this->endpoint = Client::PATH_API . '/v1/users/' . $this->apiClient->getUserId() . '/applications/' . $lpaId . '/' . $resourceType;
    }

    /**
     * Return the API response for getting the resource of the given type
     * 
     * If property not yet set, return null
     * If error, return false
     *
     * @return Response
     */
    public function getResource()
    {
        $response = $this->httpClient()->get( $this->endpoint, [
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
     *
     * @return array<Lpa>
     */
    public function getResourceList($entityClass)
    {
        $resourceList = array();
    
        do {
            $response = $this->httpClient()->get( $this->endpoint );
    
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
        $response = $this->httpClient()->get( $this->endpoint, [
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
    public function getEntityResource($entityClass, $index=null)
    {
        $response = $this->httpClient()->get( $this->endpoint . (!is_null($index) ? '/' . $index : ''), [
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
    
        $entity = new $entityClass($json);
        
        return $entity;
    }
    
    /**
     * Set the data for the given resource
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function setResource($jsonBody, $index=null)
    {
        $response = $this->httpClient()->put( $this->endpoint . (!is_null($index) ? '/' . $index : ''), [
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
     * Add data for the given resource
     *
     * @param string $jsonBody
     * @return boolean
     */
    public function addResource($jsonBody)
    {
        $response = $this->httpClient()->post( $this->endpoint, [
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
     * Delete the resource type from the LPA
     * 
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function deleteResource($index=null)
    {
        $response = $this->httpClient()->delete( $this->endpoint . (!is_null($index) ? '/' . $index : ''), [
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
     */
    public function log($response, $isSuccess=true)
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
    
        if( !isset($this->guzzleClient) ){
            $this->guzzleClient = new GuzzleClient();
        }
    
        if( $this->apiClient->getToken() != null ){
            $this->guzzleClient->setToken( $this->apiClient->getToken() );
        }
    
        return $this->guzzleClient;
    
    }
}