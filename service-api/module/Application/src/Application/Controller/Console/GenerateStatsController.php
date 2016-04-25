<?php
namespace Application\Controller\Console;

use Zend\Mvc\Controller\AbstractActionController;

class GenerateStatsController extends AbstractActionController {

    /**
     * This action is triggered daily from a cron job.
     */
    public function generateAction(){

        $cronLock = $this->getServiceLocator()->get('DynamoCronLock');

        $lockName = 'GenerateApiStats';

        // Attempt to get the cron lock...
        if( $cronLock->getLock( $lockName, (60 * 60) ) ){

            echo "Got the GenerateApiStats lock.\n";

            $this->getServiceLocator()->get('Logger')->info("This node got the GenerateApiStats cron lock for {$lockName}");

            //---

            $result = $this->getServiceLocator()->get('StatsService')->generate();

        } else {

            echo "Did not get the GenerateApiStats lock\n";

            $this->getServiceLocator()->get('Logger')->info("This node did not get the GenerateApiStats cron lock for {$lockName}");

        }

    } // function

} // class
