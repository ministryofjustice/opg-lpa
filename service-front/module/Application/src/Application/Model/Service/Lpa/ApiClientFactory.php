<?php
namespace Application\Model\Service\Lpa;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Opg\Lpa\Api\Client\Client as ApiClient;

class ApiClientFactory implements FactoryInterface {

    /**
     * Create and instance of the API Client.
     *
     * If the user identity exists, pre-set the userId and token in the client.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiClient
     */
    public function createService(ServiceLocatorInterface $serviceLocator){
        
        $client = new ApiClient();

        //---

        $auth = $serviceLocator->get('AuthenticationService');

        if ( $auth->hasIdentity() ) {

            $identity = $auth->getIdentity();
            $client->setUserId( $identity->id() );
            $client->setToken( $identity->token() );

        }

        //---
        
        return $client;

    } // function

} // class
