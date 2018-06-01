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
        $stats = $this->statsService->getApiStats();

        return new ViewModel($stats);
    }

    public function setStatsService(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }
}
