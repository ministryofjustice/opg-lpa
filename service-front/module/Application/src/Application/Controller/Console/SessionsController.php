<?php
namespace Application\Controller\Console;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

class SessionsController extends AbstractActionController
{
    use LoggerTrait;

    public function gcAction(){

        $cronLock = $this->getServiceLocator()->get('DynamoCronLock');

        $lockName = 'SessionGarbageCollection';

        // Attempt to get the cron lock...
        if( $cronLock->getLock( $lockName, ( 60 * 30 ) ) ){

            // We have the cron lock - run the job.

            echo "Got the cron lock; running Session Garbage Collection\n";

            $this->getLogger()->info("This node got the cron lock for {$lockName}");

            //---

            $saveHandler = $this->getServiceLocator()->get('SessionManager')->getSaveHandler();

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
