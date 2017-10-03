<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Zend\View\Model\ViewModel;

class StatsController extends AbstractBaseController
{
    public function indexAction()
    {
        $applicationService = $this->getServiceLocator()->get('LpaApplicationService');

        //  Get the general stats and sort - ensure the months are ordered correctly
        $generalLpaStats = $applicationService->getApiStats('lpas');
        ksort($generalLpaStats['by-month']);

        //  Get the "who are you" stats - ensure the months are ordered correctly
        $whoAreYouStats = $applicationService->getApiStats('whoareyou');
        ksort($whoAreYouStats['by-month']);

        //  Get the user stats
        $userStats = $applicationService->getAuthStats();

        //  Get the correspondence stats - ensure the months are ordered correctly
        $correspondenceStats = $applicationService->getApiStats('correspondence');
        ksort($correspondenceStats);

        //  Get the preferences and instructions stats - ensure the months are ordered correctly
        $preferencesInstructionsStats = $applicationService->getApiStats('preferencesinstructions');
        ksort($preferencesInstructionsStats);

        return new ViewModel([
            'lpas' => $generalLpaStats,
            'who' => $whoAreYouStats,
            'users' => $userStats,
            'correspondence' => $correspondenceStats,
            'preferencesInstructions' => $preferencesInstructionsStats,
        ]);
    }
}
