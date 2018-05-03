<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Stats\Stats as StatsService;
use Zend\View\Model\ViewModel;

class StatsController extends AbstractBaseController
{
    /**
     * @var StatsService
     */
    private $statsService;

    public function indexAction()
    {
        $userStats = $this->statsService->getAuthStats();

        //  Get the API stats
        $stats = $this->statsService->getApiStats();

        //  Set the auth stats in the API stats
        $stats['users'] = $userStats;

        return new ViewModel($stats);
    }

    public function setStatsService(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }
}
