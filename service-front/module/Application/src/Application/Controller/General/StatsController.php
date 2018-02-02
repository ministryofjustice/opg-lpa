<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Zend\View\Model\ViewModel;

class StatsController extends AbstractBaseController
{
    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    public function indexAction()
    {
        $applicationService = $this->lpaApplicationService;

        // Get the user stats from auth service
        $userStats = $applicationService->getAuthStats();

        // Get all other stats from api
        $stats = $applicationService->getApiStats();

        $stats['users'] = $userStats;

        return new ViewModel($stats);
    }

    public function setLpaApplicationService(LpaApplicationService $lpaApplicationService)
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
