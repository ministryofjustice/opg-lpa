<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats as StatsService;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class GenerateStatsController extends AbstractConsoleController
{
    use LoggerTrait;

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
        $lockName = 'GenerateApiStats';

        // Attempt to get the cron lock...
        if ($this->cronLock->getLock($lockName, (60 * 60))) {
            echo "Got the GenerateApiStats lock.\n";

            $this->getLogger()->info("This node got the GenerateApiStats cron lock for {$lockName}");

            $this->statsService->generate();
        } else {
            echo "Did not get the GenerateApiStats lock\n";

            $this->getLogger()->info("This node did not get the GenerateApiStats cron lock for {$lockName}");
        }
    }
}
