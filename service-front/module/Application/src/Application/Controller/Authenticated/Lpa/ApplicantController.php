<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Application\Form\Lpa\ApplicantForm;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

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
                        $applicants = $postData['attorneyList'];
                    }
                    else {
                        $applicants = explode(',', $postData['whoIsRegistering']);
                    }
                }
                
                if(!$this->getLpaApplicationService()->setWhoIsRegistering($lpaId, $applicants)) {
                    throw new \RuntimeException('API client failed to set applicant for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if(is_string($this->getLpa()->document->whoIsRegistering)) {
                $form->bind( ['whoIsRegistering' => $this->getLpa()->document->whoIsRegistering] );
            }
            else {
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
            
        }
        return new ViewModel(['form'=>$form]);
    }
}
