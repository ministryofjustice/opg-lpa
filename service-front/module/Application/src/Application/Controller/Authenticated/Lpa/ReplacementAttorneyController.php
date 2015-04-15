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
use Zend\Form\Form;
use Zend\Form\Element\Csrf;
use Application\Form\Lpa\SeedDetailsPickerForm;
use Application\Model\Service\Lpa\Metadata;

class ReplacementAttorneyController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
        
        // set hidden form for saving empty array to replacement attorneys.
        $form = new Form();
        $form->setAttribute('method', 'post');
        
        $form->add( (new Csrf('secret'))->setCsrfValidatorOptions([
                'timeout' => null,
                'salt' => sha1('Application\Form\Lpa-Salt'),
        ]));
        
        if($this->request->isPost()) {
        
            $form->setData($this->request->getPost());
        
            if($form->isValid()) {
        
                // check if replacementAttorneys is empty. If yes, set a flag in metadata, so we know user has no replacementAttorneys to be added into LPA.
                if(count($this->getLpa()->document->replacementAttorneys) == 0) { 
                    $this->getServiceLocator()->get('Metadata')->setLpaHasNoReplacementAttorneys($this->getLpa());
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        $attorneysParams = [];
        foreach($this->getLpa()->document->replacementAttorneys as $idx=>$attorney) {
            $params = [
                    'attorney' => [
                            'address'   => $attorney->address
                    ],
                    'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                    'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'idx' => $idx ]),
            ];
            
            if($attorney instanceof Human) {
                $params['attorney']['name'] = $attorney->name;
            }
            else {
                $params['attorney']['name'] = $attorney->name;
            }
            
            $attorneysParams[] = $params;
        }
        
        $viewModelParams = [
                    'addRoute'  => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'lpaId'     => $lpaId,
                    'attorneys' => $attorneysParams,
                    'form'      => $form,
        ];
        
        return new ViewModel($viewModelParams);
        
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
        
        if(($seedDetails = $this->getSeedDetails()) != null) {
            $seedDetailsPickerForm = new SeedDetailsPickerForm($seedDetails);
            $seedDetailsPickerForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
            $viewModel->seedDetailsPickerForm = $seedDetailsPickerForm;
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            if($postData->offsetExists('pick-details')) {
                // load seed data into the form or return form data in json format if request is an ajax
                $seedDetailsPickerForm->setData($this->request->getPost());
                if($seedDetailsPickerForm->isValid()) {
                    $pickIdx = $this->request->getPost('pick-details');
                    if(is_array($seedDetails) && array_key_exists($pickIdx, $seedDetails)) {
                        $actorData = $seedDetails[$pickIdx]['data'];
                        $formData = $this->flattenData($actorData);
                        if ( $this->getRequest()->isXmlHttpRequest() ) {
                            return new JsonModel($formData);
                        }
                        else {
                            $form->bind($formData);
                        }
                    }
                }
            }
            else {
                // handle replacement attorney form submission
                $form->setData($postData);
                if($form->isValid()) {
                
                    // persist to the api
                    $attorney = new Human($form->getModelDataFromValidatedForm());
                    if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                        throw new \RuntimeException('API client failed to add a replacement attorney for id: '.$lpaId);
                    }
                    
                    // remove metadata flag value if exists
                    if(!is_array($this->getLpa()->metadata) ||
                        !array_key_exists(Metadata::LPA_HAS_NO_REPLACEMENT_ATTORNEYS, $this->getLpa->metadata) ||
                        ($this->getLpa()->metadata[Metadata::LPA_HAS_NO_REPLACEMENT_ATTORNEYS]!==true)) {
                            $this->getServiceLocator()->get('Metadata')->unsetLpaHasNoReplacementAttorneys($this->getLpa());
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                    }
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
        if( array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys) ) {
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
                // update with new details
                if($attorney instanceof Human) {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                }
                else {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                }
                
                // persist to the api
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
                $dob = $attorney->dob->date;
                $flattenAttorneyData['dob-date-day'] = $dob->format('d');
                $flattenAttorneyData['dob-date-month'] = $dob->format('m');
                $flattenAttorneyData['dob-date-year'] = $dob->format('Y');
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
        if( array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys) ) {
            if(!$this->getLpaApplicationService()->deleteReplacementAttorney($lpaId, $this->getLpa()->document->replacementAttorneys[$attorneyIdx]->id)) {
                throw new \RuntimeException('API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
            }
            
            // check if the deleted replacement attorney is the last one in replacementAttorneys. If yes, set a flag in metadata, so we know user has no replacementAttorneys to be added into LPA.
            if(count($this->getLpa()->document->replacementAttorneys) == 1) {
                $this->getServiceLocator()->get('Metadata')->setLpaHasNoReplacementAttorneys($this->getLpa());
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
        
        if(($seedDetails = $this->getSeedDetails(true)) != null) {
            $seedDetailsPickerForm = new SeedDetailsPickerForm($seedDetails);
            $seedDetailsPickerForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
            $viewModel->seedDetailsPickerForm = $seedDetailsPickerForm;
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            if($postData->offsetExists('pick-details')) {
                // load seed data into the form or return form data in json format if request is an ajax
                $seedDetailsPickerForm->setData($this->request->getPost());
                if($seedDetailsPickerForm->isValid()) {
                    $pickIdx = $this->request->getPost('pick-details');
                    if(is_array($seedDetails) && array_key_exists($pickIdx, $seedDetails)) {
                        $actorData = $seedDetails[$pickIdx]['data'];
                        $formData = $this->flattenData($actorData);
                        if ( $this->getRequest()->isXmlHttpRequest() ) {
                            return new JsonModel($formData);
                        }
                        else {
                            $form->bind($formData);
                        }
                    }
                }
            }
            else {
                // handle trust corp form submission
                $form->setData($postData);
                if($form->isValid()) {
                
                    // persist data
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                    if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                        throw new \RuntimeException('API client failed to add trust corporation replacement attorney for id: '.$lpaId);
                    }
                    
                    // remove metadata flag value if exists
                    if(!is_array($this->getLpa()->metadata) ||
                        !array_key_exists(Metadata::LPA_HAS_NO_REPLACEMENT_ATTORNEYS, $this->getLpa->metadata) ||
                        ($this->getLpa()->metadata[Metadata::LPA_HAS_NO_REPLACEMENT_ATTORNEYS]!==true)) {
                            $this->getServiceLocator()->get('Metadata')->unsetLpaHasNoReplacementAttorneys($this->getLpa());
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }
        
        $viewModel->form = $form;
        $viewModel->addAttorneyRoute = $this->url()->fromRoute( 'lpa/replacement-attorney/add', ['lpa-id' => $lpaId] );
        
        return $viewModel;
    }
}
