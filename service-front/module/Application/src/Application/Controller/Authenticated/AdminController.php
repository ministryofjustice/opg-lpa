<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Zend\Mvc\MvcEvent;
use Application\Form\Admin\SystemMessageForm;

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
        $form = new SystemMessageForm();
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            
            $form->setData($post);
            
            if ($form->isValid()) {
                $cache = $this->getServiceLocator()->get('Cache');
                $cache->setItem('system-message', $post['message']);
            }
        }
        return new ViewModel(['form'=>$form]);

    }
}
