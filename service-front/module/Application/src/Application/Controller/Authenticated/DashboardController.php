<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function cloneAction()
    {
        
    }
    
    public function deleteLpaAction()
    {
        
    }
}
