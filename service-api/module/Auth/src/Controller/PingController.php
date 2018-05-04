<?php
namespace Auth\Controller;

use Auth\Model\Service\DataAccess\Mongo\Factory\DatabaseFactory;
use Auth\Model\Service\DataAccess\Mongo\Factory\ManagerFactory;
use MongoDB\Database;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractActionController;

class PingController extends AbstractActionController {

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Database
     */
    private $database;

    public function __construct(Manager $manager, Database $database)
    {
        $this->manager = $manager;
        $this->database = $database;
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

        $results = [
            'ok' => $allOk,
            'database' => (isset($mongoOK))?$mongoOK:false
        ];

        //---

        return new JsonModel($results);

    }


    /**
     * Checks we can connect to Mongo.
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

} // class
