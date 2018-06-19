<?php

namespace Application\Controller\Console;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class AccountCleanupController extends AbstractConsoleController
{
    use LoggerTrait;

    /**
     * @var DynamoCronLock
     */
    private $cronLock;

    /**
     * @var AccountCleanupService
     */
    private $accountCleanupService;

    /**
     * @param DynamoCronLock $cronLock
     * @param AccountCleanupService $accountCleanupService
     */
    public function __construct(DynamoCronLock $cronLock, AccountCleanupService $accountCleanupService)
    {
        $this->cronLock = $cronLock;
        $this->accountCleanupService = $accountCleanupService;
    }

    /**
     * This action is triggered daily from a cron job.
     */
    public function cleanupAction()
    {
        $lockName = 'AccountCleanup';

        // Attempt to get the cron lock...
        if ($this->cronLock->getLock($lockName, (60 * 60))) {
            echo "Got the AccountCleanup lock; running Cleanup\n";

            $this->getLogger()->info("This node got the AccountCleanup cron lock for {$lockName}");

            $this->accountCleanupService->cleanup();
        } else {
            echo "Did not get the AccountCleanup lock\n";

            $this->getLogger()->info("This node did not get the AccountCleanup cron lock for {$lockName}");
        }
    }
}
