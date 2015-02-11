<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Application\Controller\AbstractLpaController;
use Application\Form\Lpa\AttorneyForm;

class PrimaryAttorneyController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
             
        if( count($this->getLpa()->document->primaryAttorneys) > 0 ) {
            
            $attorneysParams = [];
            foreach($this->getLpa()->document->primaryAttorneys as $idx=>$attorney) {
                if($attorney instanceof Human) {
                    $attorneysParams[] = [
                            'attorney' => [
                                    'name'      => $attorney->name->__toString(),
                                    'address'   => $attorney->address->__toString()
                            ],
                            'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'person-index' => $attorney->id ]),
                            'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'person-index' => $attorney->id ]),
                    ];
                }
                else {
                    $attorneysParams[] = [
                            'attorney' => [
                                    'name'      => $attorney->name->__toString(),
                                    'address'   => $attorney->address->__toString()
                            ],
                            'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit-trust', ['lpa-id' => $lpaId]),
                            'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete-trust', ['lpa-id' => $lpaId]),
                    ];
                }
            }
            
            return new ViewModel([
                    'addRoute'    => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>999] ),
                    'lpaId'     => $lpaId,
                    'attorneys' => $attorneysParams,
                    'nextRoute' => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
            
        }
        else {
            
            return new ViewModel([
                    'addRoute'    => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'lpaId'     => $lpaId,
            ]);
            
        }
    }
    
    public function addAction()
    {
        $viewModel = new ViewModel();
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new AttorneyForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
            
                // persist data
                $attorney = new Human($form->getModelizedData());
                if( !$this->getLpaApplicationService()->addPrimaryAttorney($lpaId, $attorney) ) {
                    throw new \RuntimeException('API client failed to add an attorney for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $viewModel;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        
        $viewModel->form = $form;
        
        if(!$this->hasTrust()) {
            $viewModel->trustCorporationRoute = $this->url()->fromRoute( 'lpa/primary-attorney/add-trust', ['lpa-id' => $lpaId] );
        }
        
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
        
        $form = new AttorneyForm();
        
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('person-index');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
                // persist data
                $attorney = new Human($form->getModelizedData());
                
                if(!$this->getLpaApplicationService()->setPrimaryAttorney($lpaId, $attorney, $attorneyIdx)) {
                    throw new \RuntimeException('API client failed to update attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $viewModel;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $attorney = $this->getLpa()->document->primaryAttorneys[$attorneyIdx]->flatten();
            $attorney['dob-date'] = $this->getLpa()->document->donor->dob->date->format('Y-m-d');
            $form->bind($attorney);
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
    public function deleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('person-index');
        
        if(!$this->getLpaApplicationService()->deletePrimaryAttorney($lpaId, $attorneyIdx)) {
            throw new \RuntimeException('API client failed to update attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
        }
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            return $this->response;
        }
        else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
        }
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
