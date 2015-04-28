<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class AdminController extends AbstractAuthenticatedController
{
    private function adminCheck()
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
