<?php

namespace Application\Controller;

use DynamoQueue\Queue\Client as DynamoQueueClient;
use GuzzleHttp\Client as GuzzleClient;
use MongoDB\Database;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 * Checks *this* API service is operating correctly. Includes:
 *  - Checking we can talk to Mongo
 *  - #todo - Checking we can communicate with the PDF 2 service.
 *
 * Class PingController
 * @package Application\Controller
 */
class PingController extends AbstractActionController
{
    use LoggerTrait;

    /**
     * @var DynamoQueueClient
     */
    private $dynamoQueueClient;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var string
     */
    private $authPingEndPoint;

    /**
     * PingController constructor
     *
     * @param DynamoQueueClient $dynamoQueueClient
     * @param Manager $manager
     * @param Database $database
     * @param $authPingEndPoint
     */
    public function __construct(DynamoQueueClient $dynamoQueueClient, Manager $manager, Database $database, $authPingEndPoint)
    {
        $this->dynamoQueueClient = $dynamoQueueClient;
        $this->manager = $manager;
        $this->database = $database;
        $this->authPingEndPoint = $authPingEndPoint;
    }


    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     */
    public function elbAction(){

        $response = $this->getResponse();

        //---

        // Include a sanity check on ssl certs

        $path = '/etc/ssl/certs/b204d74a.0';

        if( !is_link($path) | !is_readable($path) || !is_link($path) || empty(file_get_contents($path)) ){

            $response->setStatusCode(500);
            $response->setContent('Sad face');

        } else {

            $response->setContent('Happy face');

        }

        //---

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
        // Check Auth

        $result['auth'] = $this->auth();

        //----------------------------
        // Check DynamoDB


        $result['queue'] = [
            'ok' => false,
            'details' => [
                'available' => false,
                'length' => null,
                'lengthAcceptable' => false,
            ],
        ];

        try {
            $count = $this->dynamoQueueClient->countWaitingJobs();

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

        $this->getLogger()->info('PingController results', $result);

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

        $pingCommand = new Command(['ping' => 1]);
        $this->manager->executeCommand($this->database->getDatabaseName(), $pingCommand);

        //---

        $primaryFound = false;

        foreach( $this->manager->getServers() as $server ){

            // If the connection is to primary, all is okay.
            if( $server->isPrimary() ){
                $primaryFound = true;
                break;
            }

        }

        //---

        return $primaryFound;

    }

    /**
     * Check we can ping Auth
     *
     * @return array
     */
    private function auth(){

        $result = array( 'ok'=> false, 'details'=>array( '200'=>false ) );

        try {
            $client = new GuzzleClient();

            $response = $client->get(
                $this->authPingEndPoint,
                ['connect_timeout' => 5, 'timeout' => 10]
            );

            // There should be no JSON if we don't get a 200, so return.
            if ($response->getStatusCode() != 200) {
                return $result;
            }

            //---

            $result['details']['200'] = true;

            $api = json_decode($response->getBody(), true);

            $result['ok'] = $api['ok'];
            $result['details'] = $result['details'] + $api;

        } catch( \Exception $e ){ /* Don't throw exceptions; we just return ok==false */ }

        return $result;

    } // function

} // class
