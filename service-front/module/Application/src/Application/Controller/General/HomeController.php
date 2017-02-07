<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Zend\View\Model\ViewModel;

class HomeController extends AbstractBaseController
{
    public function indexAction()
    {
        return new ViewModel([
            'lpaFee' => Calculator::getFullFee()
        ]);
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
