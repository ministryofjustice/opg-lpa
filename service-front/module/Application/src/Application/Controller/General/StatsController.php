<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class StatsController extends AbstractBaseController
{

    public function indexAction()
    {

        $generalLpaStats = $this->getServiceLocator()->get('LpaApplicationService')->getApiStats( 'lpas' );

        // Ensure the months are ordered correctly.
        ksort($generalLpaStats['by-month']);

        //---

        $whoAreYouStats = $this->getServiceLocator()->get('LpaApplicationService')->getApiStats( 'whoareyou' );

        // Ensure the months are ordered correctly.
        ksort($whoAreYouStats['by-month']);

        //---

        $userStats = $this->getServiceLocator()->get('LpaApplicationService')->getAuthStats();

        //---

        $welshLanguageStats = $this->getServiceLocator()->get('LpaApplicationService')->getApiStats( 'welshlanguage' );

        //---

        return new ViewModel([
            'lpas' => $generalLpaStats,
            'who' => $whoAreYouStats,
            'users' => $userStats,
            'welshLanguage' => $welshLanguageStats,
        ]);

    } // function

} // class
