<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats as StatsService;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class GenerateStatsController extends AbstractConsoleController
{
    /**
     * @var DynamoCronLock
     */
    private $cronLock;

    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * @param DynamoCronLock $cronLock
     * @param StatsService $statsService
     */
    public function __construct(DynamoCronLock $cronLock, StatsService $statsService)
    {
        $this->cronLock = $cronLock;
        $this->statsService = $statsService;
    }

    /**
     * This action is triggered daily from a cron job.
     */
    public function generateAction()
    {
        $consoleMessage = "Did not get the GenerateApiStats lock\n";

        //  Attempt to get the cron lock before executing the service
        if ($this->cronLock->getLock('GenerateApiStats')) {
            $consoleMessage  = "Got the GenerateApiStats lock.\n";

            $this->statsService->generate();
        }

        echo $consoleMessage;
    }
}
