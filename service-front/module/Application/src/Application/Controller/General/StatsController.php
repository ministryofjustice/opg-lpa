<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Zend\View\Model\ViewModel;

class StatsController extends AbstractBaseController
{
    public function indexAction()
    {
        $applicationService = $this->getAuthenticationService();

        // Get the user stats from auth service
        $userStats = $applicationService->getAuthStats();

        // Get all other stats from api
        $stats = $applicationService->getApiStats();

        $stats['users'] = $userStats;

        return new ViewModel($stats);
    }
}
