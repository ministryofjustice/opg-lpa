<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class MoreInfoRequiredController extends AbstractLpaController
{
    public function indexAction()
    {
        return new ViewModel(['lpaId' => $this->getLpa()->id]);
    }
}
