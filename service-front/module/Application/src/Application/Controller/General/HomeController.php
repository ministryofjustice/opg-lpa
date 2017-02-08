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
        return $this->redirect()->toUrl( $this->config()['redirects']['index'] );
    }

    public function enableCookieAction(){
        return new ViewModel();
    }

    public function termsAction(){
        return new ViewModel();
    }

}
