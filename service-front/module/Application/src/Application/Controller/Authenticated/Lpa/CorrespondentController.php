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
use Application\Form\Lpa\CorrespondentForm;
use Application\Form\Lpa\CorrespondentSwitcherForm;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\User\Address;
use Zend\View\Model\JsonModel;
use Zend\Form\Form;
use Zend\Form\Element\Csrf;

class CorrespondentController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $lpaId = $this->getLpa()->id;
        
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
        $form = new Form();
        $form->setAttribute('method', 'post');
        
        $form->add( (new Csrf('secret'))->setCsrfValidatorOptions([
                'timeout' => null,
                'salt' => sha1('Application\Form\Lpa-Salt'),
        ]));
        
        if($this->request->isPost()) {
            
            $form->setData($this->request->getPost());
            
            if($form->isValid()) {
                
                // save default correspondent if it has not been set
                if($this->getLpa()->document->correspondent === null) {
                
                    $applicants = $this->getLpa()->document->whoIsRegistering;
                
                    // work out the default correspondent - donor or an attorney.
                    if($applicants == 'donor') {
                        $correspondent = $this->getLpa()->document->donor;
                    }
                    else {
                        $firstAttorneyId = array_values($applicants)[0];
                        foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                            if($attorney->id == $firstAttorneyId) {
                                $correspondent = $attorney;
                                break;
                            }
                        }
                    }
                    
                    // save correspondent via api
                    if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, new Correspondence([
                            'who'       => (($this->getLpa()->document->whoIsRegistering=='donor')?'donor':'attorney'),
                            'name'      => ((!$correspondent instanceof TrustCorporation)? $correspondent->name:null),
                            'company'   => (($correspondent instanceof TrustCorporation)? $correspondent->name:null),
                            'address'   => $correspondent->address,
                            'email'     => $correspondent->email,
                    ]))) {
                        throw new \RuntimeException('API client failed to set correspondent for id: '.$lpaId);
                    }
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        return new ViewModel([
                'form'              => $form,
                'correspondent'     => [
                        'name'      => (($correspondent->name instanceof Name)?$correspondent->name:$correspondent->name),
                        'address'   => $correspondent->address,
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
        
        $correspondentForm = new CorrespondentForm();
        $correspondentForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        $switcherForm = new CorrespondentSwitcherForm($this->getLpa());
        $switcherForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            if($postData->offsetExists('switch-to-type')) {
                $switcherForm->setData($postData);
                if($switcherForm->isValid()) {
                    switch($postData['switch-to-type']) {
                        case 'me':
                            $userSession = $this->getServiceLocator()->get('UserDetailsSession');
                            
                            $formData = [
                                    'who'=>'other',
                                    'name-title' => $userSession->user->name->title,
                                    'name-first' => $userSession->user->name->first,
                                    'name-last' => $userSession->user->name->last,
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
                            break;
                        default:
                            if(is_numeric($postData['switch-to-type'])) {
                                foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                                    if($attorney->id == $postData['switch-to-type']) {
                                        $formData = $attorney->flatten();
                                        if($attorney instanceof TrustCorporation) {
                                            $formData['company'] = $attorney->name;
                                        }
                                        $formData['who'] = 'attorney';
                                        break;
                                    }
                                }
                            }
                            else {
                                $formData = ['who'=>'other'];
                            }
                            break;
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel($formData);
                    }
                    else {
                        $correspondentForm->bind($formData);
                    }
                }
            }
            else {
                // handle correspondent form submission
                $correspondentForm->setData($postData);
                if($correspondentForm->isValid()) {
                    
                    // persist data
                    $correspondent = new Correspondence($correspondentForm->getModelDataFromValidatedForm());
                    
                    if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, $correspondent)) {
                        throw new \RuntimeException('API client failed to update correspondent for id: '.$lpaId);
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
        else {
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
        
            $correspondent = $correspondent->flatten();
            $correspondentForm->bind($correspondent);
        }
        
        $viewModel->correspondentForm = $correspondentForm;
        $viewModel->switcherForm = $switcherForm;
        
        return $viewModel;
    }
}
