<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class StatusController extends AbstractBaseController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function pingdomAction()
    {
        return new ViewModel();
    }
}
