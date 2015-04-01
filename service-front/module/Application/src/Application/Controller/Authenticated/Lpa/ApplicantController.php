<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Application\Form\Lpa\ApplicantForm;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;

class ApplicantController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = new ApplicantForm($this->getLpa());
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                if( $postData['whoIsRegistering'] == 'donor' ) {
                    $applicants = 'donor';
                }
                else {
                    if((count($this->getLpa()->document->primaryAttorneys) > 1) &&
                            ($this->getLpa()->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                        $applicants = $form->getData()['attorneyList'];
                    }
                    else {
                        $applicants = explode(',', $form->getData()['whoIsRegistering']);
                    }
                }
                
                // save applicant
                if(!$this->getLpaApplicationService()->setWhoIsRegistering($lpaId, $applicants)) {
                    throw new \RuntimeException('API client failed to set applicant for id: '.$lpaId);
                }
                
                // work out the default correspondent - donor or an attorney.
                if($applicants == 'donor') {
                    $correspondent = $this->getLpa()->document->donor;
                }
                else {
                    $firstAttorneyId = array_values($applicants)[0];
                    foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                        if($attorney->id == $firstAttorneyId) {
                            $correspondent = $attorney;
                        }
                    }
                }
                
                // save default correspondent if it has not been set
                if($this->getLpa()->document->correspondent == null) {
                    if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, new Correspondence([
                            'who'       => (($postData['whoIsRegistering'] == 'donor')?'donor':'attorney'),
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
        else {
            if(is_array($this->getLpa()->document->whoIsRegistering)) {
                if((count($this->getLpa()->document->primaryAttorneys) > 1) &&
                        ($this->getLpa()->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                    $bindingData = [
                            'whoIsRegistering'  => implode(',', array_map(function($attorney){return $attorney->id;},$this->getLpa()->document->primaryAttorneys)),
                            'attorneyList'      => $this->getLpa()->document->whoIsRegistering,
                    ];
                }
                else {
                    $bindingData = ['whoIsRegistering' => implode(',', $this->getLpa()->document->whoIsRegistering)];
                }
                
                $form->bind( $bindingData );
            }
            else {
                $form->bind( ['whoIsRegistering' => $this->getLpa()->document->whoIsRegistering] );
            }
        }
        return new ViewModel(['form'=>$form]);
    }
}
