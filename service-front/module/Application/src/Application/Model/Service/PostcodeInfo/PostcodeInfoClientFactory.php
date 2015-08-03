<?php
namespace Application\Model\Service\PostcodeInfo;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use MinistryOfJustice\PostcodeInfo\Client\Client as PostcodeInfoClient;

class PostcodeInfoClientFactory implements FactoryInterface {

    /**
     * Create and instance of the Postcode Info Client.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return PostcodeInfoClient
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $config = $serviceLocator->get('config')['address']['postcode_info'];
        
        $client = new PostcodeInfoClient(
            $config['token'],
            $config['uri']
        );
        
        return $client;

    } // function

} // class
