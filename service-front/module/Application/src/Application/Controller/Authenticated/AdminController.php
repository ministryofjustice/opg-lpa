<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Zend\Mvc\MvcEvent;

class AdminController extends AbstractAuthenticatedController
{
    /**
     * Ensure user is allowed to access admin functions
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $userEmail = (string)$this->getUserDetails()->email;
        
        $adminAccounts = $this->getServiceLocator()->get('config')['admin']['accounts'];
        
        $isAdmin = in_array($userEmail, $adminAccounts);
        
        if (!$isAdmin) {
            $this->redirect()->toRoute('home');
        }
        
        return true;
    }
    
    public function statsAction()
    {
        return new ViewModel();
    }
}
