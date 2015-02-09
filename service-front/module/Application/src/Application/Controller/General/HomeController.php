<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class HomeController extends AbstractBaseController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function redirectAction()
    {
        # TODO - Remove die
        die( "This page will redirect to: " . $this->config()['redirects']['index'] );
        return $this->redirect()->toUrl( $this->config()['redirects']['index'] );
    }

    public function enableCookieAction(){
        return new ViewModel();
    }

}
