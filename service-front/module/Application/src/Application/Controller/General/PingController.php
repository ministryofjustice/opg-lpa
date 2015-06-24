<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class PingController extends AbstractBaseController {

    public function indexAction()
    {

        $this->doChecks();

        return new ViewModel();
    }

    public function jsonAction()
    {

        $this->doChecks();

        die('json');
    }
    
    public function pingdomAction()
    {
        return new ViewModel();
    }

    private function doChecks(){

        // All v2 stuff.
        $status = $this->getServiceLocator()->get('SiteStatus');

        $a = $status->check();

        // And the v1 Healthcheck.


    }

} // class
