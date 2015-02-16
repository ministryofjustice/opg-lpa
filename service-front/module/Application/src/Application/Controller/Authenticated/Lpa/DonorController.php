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

class DonorController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $lpaId = $this->getLpa()->id;
        
        $donor = $this->getLpa()->document->donor;
        if( $donor instanceof Donor ) {
            
            return new ViewModel([
                    'donor'         => [
                            'name'  => $donor->name->__toString(),
                            'address' => $donor->address->__toString(),
                    ],
                    'editDonorUrl'  => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] ),
                    'nextRoute'     => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
        }
        else {
            return new ViewModel([ 'addRoute' => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ) ]);
        }
        
    }
    
    public function addAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        if( $this->getLpa()->document->donor instanceof Donor ) {
            $this->redirect()->toRoute('lpa/donor', ['lpa-id'=>$lpaId]);
        }
        
        $viewModel = new ViewModel();
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $form = new DonorForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            if($form->isValid()) {
                
                // persist data
                $donor = new Donor($form->getModelizedData());
                if(!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
                    throw new \RuntimeException('API client failed to save LPA donor for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $this->response;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
    public function editAction()
    {
        $viewModel = new ViewModel();
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new DonorForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $postData['canSign'] = (bool) $postData['canSign'];
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
                // persist data
                $donor = new Donor($form->getModelizedData());
                
                if(!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
                    throw new \RuntimeException('API client failed to update LPA donor for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $this->response;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $donor = $this->getLpa()->document->donor->flatten();
            $donor['dob-date'] = $this->getLpa()->document->donor->dob->date->format('Y-m-d');
            $form->bind($donor);
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
}
