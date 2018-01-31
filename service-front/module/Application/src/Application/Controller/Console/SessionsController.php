<?php
namespace Application\Controller\Console;

use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\System\DynamoCronLock;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

class SessionsController extends AbstractActionController
{
    use LoggerTrait;

    /**
     * @var DynamoCronLock
     */
    private $dynamoCronLock;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * SessionsController constructor.
     * @param DynamoCronLock $dynamoCronLock
     * @param SessionManager $sessionManager
     */
    public function __construct(DynamoCronLock $dynamoCronLock, SessionManager $sessionManager)
    {
        $this->dynamoCronLock = $dynamoCronLock;
        $this->sessionManager = $sessionManager;
    }

    public function gcAction(){

        $cronLock = $this->dynamoCronLock;

        $lockName = 'SessionGarbageCollection';

        // Attempt to get the cron lock...
        if( $cronLock->getLock( $lockName, ( 60 * 30 ) ) ){

            // We have the cron lock - run the job.

            echo "Got the cron lock; running Session Garbage Collection\n";

            $this->getLogger()->info("This node got the cron lock for {$lockName}");

            //---

            $saveHandler = $this->sessionManager->getSaveHandler();

            if( $saveHandler instanceof DynamoDbSessionHandler ){
                $saveHandler->garbageCollect();
            }

            //---

            $this->getLogger()->info("Finished running Session Garbage Collection");

        } else {

            echo "Did not get the session cron lock\n";

            $this->getLogger()->info("This node did not get the cron lock for {$lockName}");

        }

    }

} // class
