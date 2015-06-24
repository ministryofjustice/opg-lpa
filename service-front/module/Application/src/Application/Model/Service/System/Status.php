<?php
namespace Application\Model\Service\System;

use Exception;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use GuzzleHttp\Client as GuzzleClient;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * Services:
     *  - API 2
     *  - Auth
     *  - Postcode Anywhere
     *  - SendGird
     *  - RedisFront
     */

    public function check(){

        $result = array();

        //-----------------------------------
        // Check API 2

        $result['api'] = $this->api();


        //-----------------------------------
        // Check v1 (#v1Code)

        // This is just to check the V1 Module is enabled.
        // Otherwise we skip v1 checks.
        if( $this->getServiceLocator()->has('ProxyDashboard') ){

            $result['v1'] = $this->v1();

        }
        // end #v1Code

        //-----------------------------------
        // Postcode anywhere

        var_dump($result); die;

    }

    //------------------------------------------------------------------------

    /**
     * This while method is #v1Code code.
     */
    private function v1(){

        $result = array('ok' => false, 'details' => array('200' => false));

        try {

            $client = new GuzzleClient();
            $client->setDefaultOption('exceptions', false);

            $response = $client->get( 'http://front.local/manage/availability?healthcheck=1' );

            if ($response->getStatusCode() != 200) {
                return $result;
            }

            //---

            $result['details']['200'] = true;


        } catch (Exception $e) { /* Don't throw exceptions; we just return ok==false */ }

        return $result;

    } // function

    //------------------------------------------------------------------------

    private function api(){

        $result = array( 'ok'=> false, 'details'=>array( '200'=>false ) );

        try {

            $config = $this->getServiceLocator()->get('config')['api_client'];

            $client = new GuzzleClient();
            $client->setDefaultOption('exceptions', false);

            $response = $client->get($config['api_uri'] . '/ping');

            // There should be no JSON if we don't get a 200, so return.
            if ($response->getStatusCode() != 200) {
                return $result;
            }

            //---

            $result['details']['200'] = true;

            $api = $response->json();

            $result['ok'] = $api['ok'];
            $result['details'] = $result['details'] + $api;

        } catch( Exception $e ){ /* Don't throw exceptions; we just return ok==false */ }

        return $result;

    } // function

} // class
