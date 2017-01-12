<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class RegisterLpaController extends AbstractLpaController
{

    protected $contentHeader = 'registration-partial.phtml';

    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        return new ViewModel([
                'lpaType'       => ("property-and-financial" == $this->getLpa()->document->type)? 'Property and financial affairs':'Health and welfare',
                'donorName'     => $this->getLpa()->document->donor->name,
                'creationDate'  => $this->getLpa()->createdAt->format('d/m/Y'),
                'editRoute'     => $this->url()->fromRoute('lpa/instructions', ['lpa-id'=>$lpaId]),
                'deleteRoute'   => $this->url()->fromRoute('user/dashboard/delete-lpa', ['lpa-id'=>$lpaId]),
                'nextRoute'     => $this->url()->fromRoute('lpa/applicant', ['lpa-id'=>$lpaId]),
        ]);
    }
}
