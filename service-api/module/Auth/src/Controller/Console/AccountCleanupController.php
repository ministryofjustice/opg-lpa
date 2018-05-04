<?php
namespace Auth\Controller\Console;

use Auth\Model\Service\AccountCleanupService;
use Auth\Model\Service\System\DynamoCronLock;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;

class AccountCleanupController extends AbstractActionController
{
    use LoggerTrait;

    /**
     * @var AccountCleanupService
     */
    private $accountCleanupService;

    /**
     * @var DynamoCronLock
     */
    private $dynamoCronLock;

    /**
     * @var array
     */
    private $config;

    public function __construct(
        AccountCleanupService $accountCleanupService,
        DynamoCronLock $dynamoCronLock,
        array $config
    ) {
        $this->accountCleanupService = $accountCleanupService;
        $this->dynamoCronLock = $dynamoCronLock;
        $this->config = $config;
    }

    /**
     * This action is triggered daily from a cron job.
     */
    public function cleanupAction(){

        $cronLock = $this->dynamoCronLock;

        $lockName = 'AccountCleanup';

        // Attempt to get the cron lock...
        if( $cronLock->getLock( $lockName, (60 * 60) ) ){

            echo "Got the AccountCleanup lock; running Cleanup\n";

            $this->getLogger()->info("This node got the AccountCleanup cron lock for {$lockName}");

            //---

            $callbackUrl = $this->config['cleanup']['notification']['callback'];

            $this->accountCleanupService->cleanup( $callbackUrl );


        } else {

            echo "Did not get the AccountCleanup lock\n";

            $this->getLogger()->info("This node did not get the AccountCleanup cron lock for {$lockName}");

        }


    } // function

} // class
