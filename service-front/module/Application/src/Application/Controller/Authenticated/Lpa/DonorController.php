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
use Application\Form\Lpa\DonorForm;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;

class DonorController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        if($this->getLpa() instanceof Lpa) {
            
            $lpaId = $this->getLpa()->id;
            
            return new ViewModel([
                    'editDonorUrl'  => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] ),
                    'nextRoute'     => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
        }
        else {
            return new ViewModel();
        }
        
    }
    
    public function addAction()
    {
        $form = new DonorForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            if($form->isValid()) {
                
                $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                $donor = new Donor($form->getDataForModel());
                if(!$this->lpaService->setDonor($lpaId, $donor)) {
                    throw new \RuntimeException('API client failed to save LPA donor for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                
            }
        }
        
        $viewModel = new ViewModel(['form'=>$form]);
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        return $viewModel;
    }
    
    public function editAction()
    {
        $form = new DonorForm();
        
        $form->bind($this->getLpa()->document->donor);
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
//                 $this->redirect('lpa/donor', ['lpa-id'=>$this->request->getPost('lpa-id')]);
            }
        }
        
        $viewModel = new ViewModel(['form'=>$form]);
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        return $viewModel;
    }
}
