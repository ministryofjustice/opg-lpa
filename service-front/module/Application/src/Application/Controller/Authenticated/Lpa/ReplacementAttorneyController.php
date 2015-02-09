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

class ReplacementAttorneyController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function addAction()
    {
        return new ViewModel();
    }
    
    public function editAction()
    {
        return new ViewModel();
    }
    
    public function deleteAction()
    {
        // @todo delete a replacement attorney
        
        $this->redirect()->toRoute('lpa/replacement-attorney', array('lpa-id'=>$this->getEvent()->getRouteMatch()->getParam('lpa-id')));
        
        return $this->response;
    }
    
    public function addTrustAction()
    {
        return new ViewModel();
    }
    
    public function editTrustAction()
    {
        return new ViewModel();
    }
    
    public function deleteTrustAction()
    {
        // @todo delete trust from primaryAttorneys
                
        $this->redirect()->toRoute('lpa/primary-attorney', array('lpa-id'=>$this->getEvent()->getRouteMatch()->getParam('lpa-id')));
        
        return $this->response;
    }
}
