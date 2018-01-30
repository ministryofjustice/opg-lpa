<?php

namespace Application\Model\Service\System;

use Exception;
use Aws\DynamoDb\DynamoDbClient;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status
{
    /**
     * Services:
     *  - API 2
     *  - Auth
     *  - RedisFront
     *  - Postcode Anywhere #TODO
     *  - SendGird #TODO
     */

    public function check(){

        $result = [ 'ok' => false ];

        for( $i=1; $i <= 6; $i++ ){

            $result = array();

            //-----------------------------------
            // DynamoDB

            $result['dynamo'] = $this->dynamo();

            //-----------------------------------
            // Check API 2

            $result['api'] = $this->api();

            //-----------------------------------
            // Check Auth 2

            $result['auth'] = $this->auth();

            //-----------------------------------

            $ok = true;

            foreach( $result as $service ){
                $ok = $ok && $service['ok'];
            }

            $result['ok'] = $ok;
            $result['iterations'] = $i;

            if( !$result['ok'] ){ return $result; }

        }

        return $result;

    } // function

    //------------------------------------------------------------------------

    private function dynamo(){

        $result = array('ok' => false, 'details' => [
            'sessions' => false,
            'properties' => false,
            'locks' => false,
        ]);

        //------------------
        // Sessions

        try {

            $config = $this->getServiceLocator()->get('Config')['session']['dynamodb'];

            $client = new DynamoDbClient( $config['client'] );

            $details = $client->describeTable([
                'TableName' => $config['settings']['table_name']
            ]);

            if(
                $details['@metadata']['statusCode'] === 200 &&
                in_array( $details['Table']['TableStatus'], ['ACTIVE','UPDATING'] )
            ){

                // Table is okay
                $result['details']['sessions'] = true;

            }

        } catch ( Exception $e ){}


        //------------------
        // Properties

        try {

            $config = $this->getServiceLocator()->get('Config')['admin']['dynamodb'];

            $client = new DynamoDbClient( $config['client'] );

            $details = $client->describeTable([
                'TableName' => $config['settings']['table_name']
            ]);

            if(
                $details['@metadata']['statusCode'] === 200 &&
                in_array( $details['Table']['TableStatus'], ['ACTIVE','UPDATING'] )
            ){

                // Table is okay
                $result['details']['properties'] = true;

            }

        } catch ( Exception $e ){}



        //------------------
        // Locks

        try {

            $config = $this->getServiceLocator()->get('Config')['cron']['lock']['dynamodb'];

            $client = new DynamoDbClient( $config['client'] );

            $details = $client->describeTable([
                'TableName' => $config['settings']['table_name']
            ]);

            if(
                $details['@metadata']['statusCode'] === 200 &&
                in_array( $details['Table']['TableStatus'], ['ACTIVE','UPDATING'] )
            ){

                // Table is okay
                $result['details']['locks'] = true;

            }

        } catch ( Exception $e ){}

        //----

        // ok is true if and only if all values in details are true.
        $result['ok'] = array_reduce(
            $result['details'],
            function( $carry , $item ){ return $carry && $item; },
            true // initial
        );

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

    //------------------------------------------------------------------------

    private function auth(){

        $result = array( 'ok'=> false, 'details'=>array( '200'=>false ) );

        try {

            $config = $this->getServiceLocator()->get('config')['api_client'];

            $client = new GuzzleClient();
            $client->setDefaultOption('exceptions', false);

            $response = $client->get(
                $config['auth_uri'] . '/ping',
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
