<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class StatsController extends AbstractBaseController
{
    public function indexAction()
    {
        return new ViewModel();
    }
}
