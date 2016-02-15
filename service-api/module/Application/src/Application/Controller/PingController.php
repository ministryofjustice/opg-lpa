<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

/**
 * Checks *this* API service is operating correctly. Includes:
 *  - Checking we can talk to Mongo
 *  - #todo - Checking we can communicate with the PDF 2 service.
 *
 * Class PingController
 * @package Application\Controller
 */
class PingController extends AbstractActionController {

    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     */
    public function elbAction(){

        $response = $this->getResponse();

        //$response->setStatusCode(500);
        $response->setContent('Happy face');

        return $response;

    } // function


    public function indexAction(){

        $result = array();

        //----------------------------
        // Check Mongo

        $result['database'] = [ 'ok' => false ];

        try {

            $result['database'] = [ 'ok' => $this->canConnectToMongo() ];

        } catch( \Exception $e ){}


        //----------------------------
        // Check DynamoDB

        $result['queue'] = [
            'ok' => false,
            'details' => [
                'available' => false,
                'length' => 'unknown',
                'lengthAcceptable' => false,
            ],
        ];

        try {

            $dynamoQueue = $this->getServiceLocator()->get('DynamoQueueClient');

            $count = $dynamoQueue->countWaitingJobs();

            if( !is_int($count) ){
                throw new \Exception('Invalid count returned');
            }

            //---

            $result['queue']['details'] = [
                'available' => true,
                'length' => $count,
                'lengthAcceptable' => ( $count < 50 ),
            ];

            $result['queue']['ok'] = $result['queue']['details']['lengthAcceptable'];

        } catch( \Exception $e ){}

        //----------------

        // Is everything true?
        $result['ok'] = $result['queue']['ok'] && $result['database']['ok'];
        
        $this->getServiceLocator()->get('Logger')->info(
            'PingController results',
            $result
        );
        
        //---

        return new JsonModel($result);

    }

    /**
     * Checks we can connect to Mongo.
     *
     * THis could be extended to also check if we can see the relevant collections.
     *
     * @return bool
     */
    private function canConnectToMongo(){

        $connection = $this->getServiceLocator()->get('Mongo-Default');

        $connection->connect();

        //---

        $primaryFound = false;

        foreach( $connection->getConnections() as $server ){

            // If the connection is to primary, all is okay.
            if( $server['connection']['connection_type_desc'] == 'PRIMARY' ){
                $primaryFound = true;
                break;
            }

        }

        //---

        return $primaryFound;

    }

} // class
