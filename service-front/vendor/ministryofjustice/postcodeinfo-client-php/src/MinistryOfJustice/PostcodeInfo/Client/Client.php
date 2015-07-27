<?php
namespace MinistryOfJustice\PostcodeInfo\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiEndpoint;
    
    /**
     * @var GuzzleClient
     */
    private $guzzleClient;
    
    /**
     * @param string $apiKey
     * @param string $apiEndpoint
     */
    public function __construct($apiKey, $apiEndpoint)
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $apiEndpoint;
    }
    
    /**
     * Lookup information for the given postcode
     * and return the contents in a Postcode object
     * 
     * @param  string $postcode
     * @return Postcode
     */
    public function lookupPostcode($postcode)
    {
        $postcodeObj = new Postcode();
        
        $path = $this->apiEndpoint . '/addresses/?postcode=' . $postcode;

        $response = $this->client()->get( $path, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->getApiKey(),
            ]
        ]);
        
        if ($response->getStatusCode() != 200) {
            
            $postcodeObj->setIsValid(false);
            
        } else {
            
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
        }
        
        return $postcodeObj;
    }
    
    /**
     * Get general information for the postcode area (local authority, centre point)
     * 
     * @param  Postcode $postcode
     * @return Postcode
     */
    public function addGeneralInformation(Postcode $postcodeObj, $postcode)
    {
        $path = $this->apiEndpoint . '/postcodes/' . $postcode . '/';
        
        $response = $this->client()->get( $path, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->getApiKey(),
            ]
        ]);
        
        $responseArray = json_decode($response->getBody(), true);

        if (count($responseArray) > 0) {
            
            if (isset($responseArray['centre']) && $responseArray['centre'] != null) {
            
                $centrePoint = new Point();
            
                $centrePoint->setType($responseArray['centre']['type']);
                $centrePoint->setLatitude($responseArray['centre']['coordinates'][0]);
                $centrePoint->setLongitude($responseArray['centre']['coordinates'][1]);
            
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
    
    public function client()
    {
        if ( !isset($this->guzzleClient) ) {
            $this->guzzleClient = new GuzzleClient();
        }
        
        return $this->guzzleClient;
    }
    
    /**
     * @return the $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return the $apiEndpoint
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }

    /**
     * @param string $apiEndpoint
     */
    public function setApiEndpoint($apiEndpoint)
    {
        $this->apiEndpoint = $apiEndpoint;
    }
    
}
