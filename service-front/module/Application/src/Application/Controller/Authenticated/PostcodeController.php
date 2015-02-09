<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class PostcodeController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new ViewModel();
    }
}
