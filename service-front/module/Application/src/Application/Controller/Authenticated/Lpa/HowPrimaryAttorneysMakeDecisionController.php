<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class HowPrimaryAttorneysMakeDecisionController extends AbstractLpaController
{

    protected $contentHeader = 'creation-partial.phtml';

    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm');

        $lpaId = $this->getLpa()->id;

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            if($postData['how'] != PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(
                        'how'
                );
            }

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $decisions = $this->getLpa()->document->primaryAttorneyDecisions;
                }
                else {
                    $decisions = $this->getLpa()->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                }

                $howAttorneysAct = $form->getData()['how'];

                if($howAttorneysAct == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $form->getData()['howDetails'];
                }
                else {
                    $howDetails = null;
                }

                if(($decisions->how !== $howAttorneysAct) || ($decisions->howDetails !== $howDetails)) {

                    $decisions->how = $howAttorneysAct;
                    $decisions->howDetails = $howDetails;

                    // persist data
                    if(!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpaId, $decisions)) {
                        throw new \RuntimeException('API client failed to set primary attorney decisions for id: '.$lpaId);
                    }

                    $this->cleanUpReplacementAttorneyDecisions();
                }

                return $this->moveToNextRoute();
            }
        }
        else {
            $form->bind($this->getLpa()->document->primaryAttorneyDecisions->flatten());
        }

        return new ViewModel(['form'=>$form]);
    }

    private function cleanUpReplacementAttorneyDecisions(){
        $lpa = $this->getServiceLocator()->get('LpaApplicationService')->getApplication((int) $this->getLpa()->id);
        $RACleanupService = $this->getServiceLocator()->get('ReplacementAttorneyCleanup');
        $RACleanupService->cleanUp($lpa, $this->getLpaApplicationService());
    }
}
