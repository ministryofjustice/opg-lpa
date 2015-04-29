<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\Zend\View\Model;

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
        
        if ($userEmail != '') {
            $adminAccounts = $this->getServiceLocator()->get('config')['admin']['accounts'];
        
            $isAdmin = in_array($userEmail, $adminAccounts);
        
            if ($isAdmin) {
                return parent::onDispatch( $event );
            }
        }
        
        $this->redirect()->toRoute('home');
    }
    
    public function statsAction()
    {
        return new ViewModel();
    }
    
    public function systemMessageAction()
    {
        $cache = $this->getServiceLocator()->get('Cache');

        // Test setting
        $cache->setItem('admin-message', 'The system will be going down for maintenance on May 7th 2015');
        
        // Test getting
        $message = $cache->getItem('admin-message');
        
        return new ViewModel([
            'message'=>$message
        ]);
    }
}
