<?php
namespace V1Proxy\Model;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Application\Model\Service\ServiceDataInputInterface;

/**
 * This class us used to change the user's email address in the v1 Account Service
 *
 * Class ChangeEmailAddress
 * @package V1Proxy\Model
 */
class ChangeEmailAddress implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     *
     * @param $currentEmail
     * @param $newEmail
     */
    public function changeAddress( $currentEmail, $newEmail ){

        $client = $this->getServiceLocator()->get('ProxyOldApiClient');

        // Load the user's Account Service account.

        $response = $client->get( "http://accounts.local/query?email=".$currentEmail );
        $response = $response->json();

        // If there is no account, return...
        if( !isset($response['id']) ){
            return;
        }

        //----------------

        // Update the address.
        $client->put( "http://accounts.local/account/".$response['id'], [
            'body' => [
                'email' => strtolower($newEmail),
            ]
        ]);

    } // function


} // class
