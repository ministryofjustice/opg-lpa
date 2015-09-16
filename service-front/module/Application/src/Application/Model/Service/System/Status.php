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
     *  - RedisFront
     *  - Postcode Anywhere #TODO
     *  - SendGird #TODO
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
        // Check Redis (sessions)

        /*
        $result['sessions'] = array( 'ok' => false );

        try {

            $config = $this->getServiceLocator()->get('Config')['session']['redis']['server'];

            $redis = new \Redis();
            $redis->connect( $config['host'], $config['port'] );

            if( $redis->ping() == '+PONG' ){
                $result['sessions']['ok'] = true;
            }

        } catch ( Exception $e ){}
        */

        //-----------------------------------

        $ok = true;

        foreach( $result as $service ){
            $ok = $ok && $service['ok'];
        }

        $result['ok'] = $ok;

        return $result;

    } // function

    //------------------------------------------------------------------------

    /**
     * This whole method is #v1Code code.
     */
    private function v1(){

        $result = array('ok' => false, 'details' => array('200' => false));

        try {

            $client = new GuzzleClient();
            $client->setDefaultOption('exceptions', false);

            $response = $client->get(
                'https://frontv1-01/manage/availability?healthcheck=1',
                ['connect_timeout' => 5, 'timeout' => 20]
            );

            if ( $response->getStatusCode() == 200 ){
                $result['details']['200'] = true;
            }

            //---

            // Get the XML in array form.
            $v1 = json_encode( $response->xml() );
            $v1 = json_decode( $v1, true );

            if( is_array($v1) ){

                if( isset($v1['status']) ){

                    if( $v1['status'] == 'OK' ){ $result['ok'] = true; }

                    $result['details']['status'] = $v1['status'];

                }

            } // if

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

            $response = $client->get(
                $config['api_uri'] . '/ping',
                ['connect_timeout' => 5, 'timeout' => 10]
            );

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
