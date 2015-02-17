<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class RegisterLpaController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        //@todo remove this line once its can be set by api. 
        $this->getLpa()->completedAt = new \DateTime();
        
        $lpaId = $this->getLpa()->id;
        return new ViewModel([
                'lpaType'       => ("property-and-financial" == $this->getLpa()->document->type)? 'Property and financial affairs':'Health and welfare',
                'donorName'     => $this->getLpa()->document->donor->name->__toString(),
                'creationDate'  => $this->getLpa()->completedAt->format('d/m/Y'),
                'editRoute'     => $this->url()->fromRoute('lpa/instructions', ['lpa-id'=>$lpaId]),
                'deleteRoute'   => $this->url()->fromRoute('user/dashboard/delete-lpa', ['lpa-id'=>$lpaId]),
                'nextRoute'     => $this->url()->fromRoute('lpa/applicant', ['lpa-id'=>$lpaId]),
        ]);
    }
}
