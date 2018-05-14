<?php

namespace Auth\Controller\Version1;

use Auth\Model\Service\StatsService;
use Zend\View\Model\JsonModel;

class StatsController extends AbstractController
{
    /**
     * @var StatsService
     */
    private $statsService;

    public function indexAction()
    {
        return new JsonModel($this->statsService->getStats());
    }

    /**
     * @param StatsService $statsService
     */
    public function setStatsService(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }
}
