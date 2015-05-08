<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class StatsController extends AbstractBaseController
{

    public function indexAction()
    {

        $generalLpaStats = $this->getServiceLocator()->get('LpaApplicationService')->getApiStats( 'lpas' );

        return new ViewModel([
            'laps' => $generalLpaStats,
        ]);

    } // function

} // class
