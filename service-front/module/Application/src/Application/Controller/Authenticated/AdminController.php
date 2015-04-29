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
        
        $isAdmin = false;
        foreach ($adminAccounts as $authorisedEmail) {
            if ($authorisedEmail == $userEmail) {
                $isAdmin = true;
                break;
            }
        }
        
        if (!$isAdmin) {
            $this->getResponse()->setStatusCode(401);
            $this->redirect()->toRoute('home');
        }
        
        return true;
    }
    
    public function statsAction()
    {
        $this->adminCheck();
        
        return new ViewModel();
    }
}
