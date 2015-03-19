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
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Application\Form\Lpa\AttorneyForm;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Application\Form\Lpa\TrustCorporationForm;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Zend\View\Model\JsonModel;

class ReplacementAttorneyController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
             
        if( count($this->getLpa()->document->replacementAttorneys) > 0 ) {
            
            $attorneysParams = [];
            foreach($this->getLpa()->document->replacementAttorneys as $idx=>$attorney) {
                $params = [
                        'attorney' => [
                                'address'   => $attorney->address->__toString()
                        ],
                        'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                        'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                ];
                
                if($attorney instanceof Human) {
                    $params['attorney']['name'] = $attorney->name->__toString();
                }
                else {
                    $params['attorney']['name'] = $attorney->name;
                }
                
                $attorneysParams[] = $params;
            }
            
            return new ViewModel([
                    'addRoute'    => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'lpaId'     => $lpaId,
                    'attorneys' => $attorneysParams,
                    'nextRoute' => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
            
        }
        else {
            
            return new ViewModel([
                    'addRoute'    => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'lpaId'     => $lpaId,
                    'nextRoute' => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] ),
            ]);
            
        }
    }
    
    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/replacement-attorney/person-form.phtml');
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new AttorneyForm('replacement-attorney');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
            
                // persist data
                $attorney = new Human($form->getModelDataFromValidatedForm());
                if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                    throw new \RuntimeException('API client failed to add a replacement attorney for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        
        $viewModel->form = $form;
        
        // only provide add trust corp link if lpa has not a trust already and lpa is of PF type.
        if(!$this->hasTrust() && ($this->getLpa()->document->type == Document::LPA_TYPE_PF) ) {
            $viewModel->addTrustCorporationRoute = $this->url()->fromRoute( 'lpa/replacement-attorney/add-trust', ['lpa-id' => $lpaId] );
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
        
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');
        if(array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys)) {
            $attorney = $this->getLpa()->document->replacementAttorneys[$attorneyIdx];
        }
        
        // if attorney idx does not exist in lpa, return 404.
        if(!isset($attorney)) {
            return $this->notFoundAction();
        }
        
        if($attorney instanceof Human) {
            $form = new AttorneyForm();
            $viewModel->setTemplate('application/replacement-attorney/person-form.phtml');
        }
        else {
            $form = new TrustCorporationForm();
            $viewModel->setTemplate('application/replacement-attorney/trust-form.phtml');
        }
        
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId, 'idx'=>$attorneyIdx]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
                // persist data
                if($attorney instanceof Human) {
                    $attorney = new Human($form->getModelDataFromValidatedForm());
                }
                else {
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                }
                
                // update attorney
                if(!$this->getLpaApplicationService()->setReplacementAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update replacement attorney ' . $attorney->id . ' for id: ' . $lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $flattenAttorneyData = $attorney->flatten();
            if($attorney instanceof Human) {
                $flattenAttorneyData['dob-date'] = $this->getLpa()->document->donor->dob->date->format('Y-m-d');
            }
            
            $form->bind($flattenAttorneyData);
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
    public function deleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');
        
        $deletionFlag = true;
        if(array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys)) {
            if(!$this->getLpaApplicationService()->deleteReplacementAttorney($lpaId, $this->getLpa()->document->replacementAttorneys[$attorneyIdx]->id)) {
                throw new \RuntimeException('API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
            }
            $deletionFlag = true;
        }
        
        // if attorney idx does not exist in lpa, return 404.
        if(!$deletionFlag) {
            return $this->notFoundAction();
        }
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            return new JsonModel(['success' => true]);
        }
        else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
        }
    }
    
    public function addTrustAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/replacement-attorney/trust-form.phtml');
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        // redirect to add human attorney if lpa is of hw type or a trust was added already.
        if( ($this->getLpa()->document->type == Document::LPA_TYPE_HW) || $this->hasTrust() ) {
            $this->redirect()->toRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpaId]);
        }
        
        $form = new TrustCorporationForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
            
                // persist data
                $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                    throw new \RuntimeException('API client failed to add trust corporation replacement attorney for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        
        $viewModel->form = $form;
        $viewModel->addAttorneyRoute = $this->url()->fromRoute( 'lpa/replacement-attorney/add', ['lpa-id' => $lpaId] );
        
        return $viewModel;
    }
}
