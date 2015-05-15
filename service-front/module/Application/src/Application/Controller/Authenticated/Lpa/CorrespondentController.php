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
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\User\Address;
use Zend\View\Model\JsonModel;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;
use Application\Form\Lpa\AbstractActorForm;

class CorrespondentController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $lpaId = $this->getLpa()->id;
        
        /**
         * @var $correspondent
         * if $lpa->document->correspondent is a Correspondent object, $correspondent = $lpa->document->correspondent
         * else if applicant is donor, $correspondent = $lpa->document->donor
         * else if applicant is attorney, $correspondent is an attorney that is the first one in the applicants list.
         */
        if($this->getLpa()->document->correspondent === null) {
            if($this->getLpa()->document->whoIsRegistering == 'donor') {
                $correspondent = $this->getLpa()->document->donor;
            }
            else {
                $firstAttorneyId = array_values($this->getLpa()->document->whoIsRegistering)[0];
                foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                    if($attorney->id == $firstAttorneyId) {
                        $correspondent = $attorney;
                        break;
                    }
                }
            }
        }
        else {
            $correspondent = $this->getLpa()->document->correspondent;
        }
        
        // set hidden form for saving applicant as the default correspondent
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondenceForm', ['lpa'=>$this->getLpa()]);
        
        if($this->request->isPost()) {
            
            $form->setData($this->request->getPost());
            
            if($form->isValid()) {
                
                $validatedFormData = $form->getData();
                
                // save default correspondent if it has not been set
                if($this->getLpa()->document->correspondent === null) {
                
                    $applicants = $this->getLpa()->document->whoIsRegistering;
                
                    // work out the default correspondent - donor or an attorney.
                    if($applicants == 'donor') {
                        $correspondent = $this->getLpa()->document->donor;
                        $who = 'donor';
                    }
                    else {
                        $who = 'attorney';
                        $firstAttorneyId = array_values($applicants)[0];
                        foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                            if($attorney->id == $firstAttorneyId) {
                                $correspondent = $attorney;
                                break;
                            }
                        }
                    }
                    
                    // save correspondent via api
                    $params = [
                            'who'       => $who,
                            'name'      => ((!$correspondent instanceof TrustCorporation)? $correspondent->name:null),
                            'company'   => (($correspondent instanceof TrustCorporation)? $correspondent->name:null),
                            'address'   => $correspondent->address,
                            'email'     => $correspondent->email,
                            'phone'     => null,
                            'contactByPost'  => (bool)$validatedFormData['correspondence']['contactByPost'],
                            'contactInWelsh' => (bool)$validatedFormData['correspondence']['contactInWelsh'],
                    ];
                }
                else {
                    
                    $correspondent = $this->getLpa()->document->correspondent;
                    
                    $params = [
                            'who'       => $correspondent->who,
                            'name'      => $correspondent->name,
                            'company'   => $correspondent->company,
                            'address'   => $correspondent->address,
                            'email'     => $validatedFormData['correspondence']['contactByEmail']?$correspondent->email:null,
                            'phone'     => $validatedFormData['correspondence']['contactByPhone']?$correspondent->phone:null,
                            'contactByPost'  => (bool)$validatedFormData['correspondence']['contactByPost'],
                            'contactInWelsh' => (bool)$validatedFormData['correspondence']['contactInWelsh'],
                    ];
                }
                
                if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, new Correspondence($params))) {
                    throw new \RuntimeException('API client failed to set correspondent for id: '.$lpaId);
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        // bind data to the form and set params to the view.
        if($correspondent instanceof Correspondence) {
            $correspondentName = trim((string)$correspondent->name);
            if($correspondentName == '') {
                $correspondentName = $correspondent->company;
            }
            else {
                if($correspondent->company != null) {
                    $correspondentName .= ', '.$correspondent->company;
                }
            }
            
            $form->bind(['correspondence'=>[
                    'contactByEmail' => ($correspondent->email instanceof EmailAddress)?true:false,
                    'contactByPhone'  => ($correspondent->phone instanceof PhoneNumber)?true:false,
                    'contactByPost' => $correspondent->contactByPost,
                    'contactInWelsh'=> $correspondent->contactInWelsh,
            ]]);
        }
        else { // donor or attorney is correspondent
            $correspondentName = (string)$correspondent->name;
            vardump($correspondent->email instanceof EmailAddress);
            $form->bind(['correspondence'=>[
                    'contactByEmail' => ($correspondent->email instanceof EmailAddress)?true:false,
            ]]);
        }
        
        return new ViewModel([
                'form'              => $form,
                'correspondent'     => [
                        'name'      => $correspondentName,
                        'address'   => $correspondent->address,
                        'contactEmail' => ($correspondent->email instanceof EmailAddress)?$correspondent->email->address:null,
                        'contactPhone' => ($correspondent instanceof Correspondence && $correspondent->phone instanceof PhoneNumber)?$correspondent->phone->number:null,
                ],
                'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] )
        ]);
    }
    
    public function editAction()
    {
        $viewModel = new ViewModel();
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $correspondentForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondentForm');
        $correspondentForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        $correspondentSelection = $this->correspondentSelector($viewModel, $correspondentForm);
        if($correspondentSelection instanceof JsonModel) {
            return $correspondentSelection;
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            if(!$postData->offsetExists('switch-to-type')) {
                
                // handle correspondent form submission
                $correspondentForm->setData($postData);
                if($correspondentForm->isValid()) {
                    $correspondentFormData = $correspondentForm->getData();
                    
                    if($this->getLpa()->document->correspondent == null) {
                        $correspondent = new Correspondence($correspondentForm->getModelDataFromValidatedForm());
                    }
                    else {
                        $correspondent = new Correspondence(array_merge($correspondentForm->getModelDataFromValidatedForm(), [
                                'contactByPost'  => $this->getLpa()->document->correspondent->contactByPost,
                                'contactInWelsh' => $this->getLpa()->document->correspondent->contactInWelsh,
                        ]));
                    }
                    
                    if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, $correspondent)) {
                        throw new \RuntimeException('API client failed to update correspondent for id: '.$lpaId);
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                    }
                }
            } // if($postData->offsetExists('switch-to-type'))
            
        } //if($this->request->isPost())
        else {
            
            // if correspondent wasn't set, load applicant details into the form
            if($this->getLpa()->document->correspondent === null) {
                if($this->getLpa()->document->whoIsRegistering == 'donor') {
                    $correspondent = $this->getLpa()->document->donor;
                }
                else {
                    $firstAttorneyId = array_values($this->getLpa()->document->whoIsRegistering)[0];
                    foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                        if($attorney->id == $firstAttorneyId) {
                            $correspondent = $attorney;
                            break;
                        }
                    }
                }
            }
            else {
                // otherwise, load correspondent details into the form
                $correspondent = $this->getLpa()->document->correspondent;
            }
            
            // convert object into array.
            $correspondentDetails = $correspondent->flatten();
            
            if($correspondent instanceof TrustCorporation) {
                $correspondentDetails['company'] = $correspondent->name;
                $correspondentDetails['name-title'] = ' ';
            }
            elseif($correspondent instanceof Correspondence) {
                if($correspondent->name == null) {
                    $correspondentDetails['name-title'] = ' ';
                }
            }
            elseif($correspondent instanceof Donor) {
                $correspondentDetails['who'] = 'donor';
            }
            else {
                $correspondentDetails['who'] = 'attorney';
            }
            
            // bind data into the form
            $correspondentForm->bind($correspondentDetails);
            
        } //if($this->request->isPost())
        
        $viewModel->correspondentForm = $correspondentForm;
        
        return $viewModel;
    }
    
    protected function correspondentSelector(ViewModel $viewModel, AbstractActorForm $mainForm)
    {
        $switcherForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondentSwitcherForm', ['lpa'=>$this->getLpa(), 'user'=>$this->getServiceLocator()->get('UserDetailsSession')->user]);
        $switcherForm->setAttribute( 'action', $this->url()->fromRoute( $this->getEvent()->getRouteMatch()->getMatchedRouteName(), ['lpa-id' => $this->getLpa()->id] ) );
        $viewModel->switcherForm = $switcherForm;
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            if(!$postData->offsetExists('switch-to-type')) return;
            
            $switcherForm->setData($postData);
            
            if($switcherForm->isValid()) {
                switch($postData['switch-to-type']) {
                    case 'me':
                        $userSession = $this->getServiceLocator()->get('UserDetailsSession');
    
                        $formData = [
                                'who'=>'other',
                                'name-title' => $userSession->user->name->title,
                                'name-first' => $userSession->user->name->first,
                                'name-last'  => $userSession->user->name->last,
                                'company'    => '',
                        ];
                        if($userSession->user->address instanceof Address) {
                            $formData += [
                                    'address-address1' => $userSession->user->address->address1,
                                    'address-address2' => $userSession->user->address->address2,
                                    'address-address3' => $userSession->user->address->address3,
                                    'address-postcode' => $userSession->user->address->postcode,
                            ];
                        }
                        break;
                    case 'donor':
                        $formData = $this->getLpa()->document->donor->flatten();
                        $formData['who'] = 'donor';
                        $formData['company'] = '';
                        break;
                    default:
                        if(is_numeric($postData['switch-to-type'])) {
                            foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                                if($attorney->id == $postData['switch-to-type']) {
                                    $formData = $attorney->flatten();
                                    if($attorney instanceof TrustCorporation) {
                                        $formData['name-title'] = '';
                                        $formData['name-first'] = '';
                                        $formData['name-last'] = '';
                                        $formData['company'] = $attorney->name;
                                    }
                                    else {
                                        $formData['company'] = '';
                                    }
                                    $formData['who'] = 'attorney';
                                    break;
                                }
                            }
                        }
                        else {
                            $formData = [
                                    'who'=>'other',
                                    'name-title' => '',
                                    'name-first' => '',
                                    'name-last'  => '',
                                    'company'    => '',
                                    'address-address1' => '',
                                    'address-address2' => '',
                                    'address-address3' => '',
                                    'address-postcode' => '',
                            ];
                        }
                        break;
    
                } // switch($postData['switch-to-type'])
    
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel($formData);
                }
                else {
                    $mainForm->bind($formData);
                }
    
            } //if($switcherForm->isValid())
        }
        
    }
}
