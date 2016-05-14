<?php
namespace Application\Model\Service\AddressLookup;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use MinistryOfJustice\PostcodeInfo\Client as PostcodeInfoClient;

class PostcodeInfoClientFactory implements FactoryInterface {

    /**
     * Create and instance of the Postcode Info Client.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return PostcodeInfoClient
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $config = $serviceLocator->get('config')['address']['postcode_info'];

        return new PostcodeInfoClient([
            'httpClient' => $serviceLocator->get('HttpClient'),
            'apiKey' => $config['token'],
            'baseUrl' => (isset($config['uri'])) ? rtrim($config['uri'], '/') : null
        ]);

    } // function


} // class
