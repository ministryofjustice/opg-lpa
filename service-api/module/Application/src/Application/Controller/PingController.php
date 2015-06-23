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

    public function indexAction(){

        $allOk = true;

        //----------------------------
        // Check Mongo

        try {

            $mongoOK = $this->canConnectToMongo();

            $allOk = $allOk && $mongoOK;

        } catch( \Exception $e ){
            $allOk = false;
        }

        //----------------------------
        // Check Redis

        try {

            $config = $this->getServiceLocator()->get('config')['db']['redis']['default'];

            $redis = new \Credis_Client( $config['host'], $config['port'], $timeout = 5);

            $queue = ( $redis->ping() == '+PONG' );

            $allOk = $allOk && $queue;

        } catch( \Exception $e ){
            $allOk = false;
        }


        //---

        if( !$allOk ){
            $this->getResponse()->setStatusCode(500);
        }

        //---

        return new JsonModel([
            'ok' => $allOk,
            'database' => (isset($mongoOK))?$mongoOK:false,
            'queue' => (isset($queue))?$queue:false,
        ]);

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
