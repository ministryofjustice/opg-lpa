<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class ApplicantController extends AbstractLpaController
{
    public function indexAction()
    {
        return new ViewModel();
    }
}
