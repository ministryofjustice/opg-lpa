<?php

namespace Application\Controller\Console;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class AccountCleanupController extends AbstractConsoleController
{
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
        $consoleMessage = "Did not get the AccountCleanup lock\n";

        //  Attempt to get the cron lock before executing the service
        if ($this->cronLock->getLock('AccountCleanup')) {
            $consoleMessage = "Got the AccountCleanup lock; running Cleanup\n";

            $this->accountCleanupService->cleanup();
        }

        echo $consoleMessage;
    }
}
