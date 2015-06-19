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

class CreatedController extends AbstractLpaController
{
    
    protected $contentHeader = 'created-partial.phtml';
    
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        return new ViewModel([
                'editRoute'     => $this->url()->fromRoute('lpa/instructions', ['lpa-id'=>$lpaId]),
                'downloadRoute' => $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpaId, 'pdf-type'=>'lp1']),
                'nextRoute'     => $this->url()->fromRoute('lpa/applicant', ['lpa-id'=>$lpaId]),
        ]);
    }
}
