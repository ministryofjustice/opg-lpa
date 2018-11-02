<?php

namespace Application\Controller\Console;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class AccountCleanupController extends AbstractConsoleController
{
    /**
     * @var AccountCleanupService
     */
    private $accountCleanupService;

    /**
     * @param AccountCleanupService $accountCleanupService
     */
    public function __construct(AccountCleanupService $accountCleanupService)
    {
        $this->accountCleanupService = $accountCleanupService;
    }

    /**
     * This action is triggered daily from a cron job.
     */
    public function cleanupAction()
    {
        $this->accountCleanupService->cleanup();
    }
}
