<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;

class GenerateStatsController extends AbstractActionController
{
    use LoggerTrait;

    /**
     * @var DynamoCronLock
     */
    private $cronLock;

    /**
     * @var Stats
     */
    private $statsService;

    /**
     * GenerateStatsController constructor
     * @param DynamoCronLock $cronLock
     * @param Stats $statsService
     */
    public function __construct(DynamoCronLock $cronLock, Stats $statsService)
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

            $result = $this->statsService->generate();
        } else {
            echo "Did not get the GenerateApiStats lock\n";

            $this->getLogger()->info("This node did not get the GenerateApiStats cron lock for {$lockName}");
        }
    }
}
