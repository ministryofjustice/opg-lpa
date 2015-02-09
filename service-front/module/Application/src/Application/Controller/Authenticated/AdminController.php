<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class AdminController extends AbstractAuthenticatedController
{
    public function statsAction()
    {
        return new ViewModel();
    }
}
