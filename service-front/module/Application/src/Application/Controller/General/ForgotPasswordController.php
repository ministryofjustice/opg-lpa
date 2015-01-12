<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ForgotPasswordController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function resetPasswordAction()
    {
        return new ViewModel();
    }
}
