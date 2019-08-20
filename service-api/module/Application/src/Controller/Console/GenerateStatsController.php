<?php

namespace Application\Controller\Console;

use Application\Model\Service\System\Stats as StatsService;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class GenerateStatsController extends AbstractConsoleController
{
    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * @param StatsService $statsService
     */
    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * This action is triggered daily from a cron job.
     */
    public function generateAction()
    {
        $this->statsService->generate();
    }
}
