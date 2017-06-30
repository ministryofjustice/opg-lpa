<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class HowReplacementAttorneysMakeDecisionController extends AbstractLpaController
{
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm');

        $lpaId = $this->getLpa()->id;

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            if($postData['how'] != ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(
                        'how'
                );
            }

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                if($this->getLpa()->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                    $decisions = $this->getLpa()->document->replacementAttorneyDecisions;
                }
                else {
                    $decisions = $this->getLpa()->document->replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
                }

                $howAttorneysAct = $form->getData()['how'];

                if($howAttorneysAct == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $form->getData()['howDetails'];
                }
                else {
                    $howDetails = null;
                }

                if(($decisions->how !== $howAttorneysAct) || ($decisions->howDetails !== $howDetails)) {
                    $decisions->how = $howAttorneysAct;
                    $decisions->howDetails = $howDetails;

                    // persist data
                    if(!$this->getLpaApplicationService()->setReplacementAttorneyDecisions($lpaId, $decisions)) {
                        throw new \RuntimeException('API client failed to set replacement attorney decisions for id: '.$lpaId);
                    }
                }

                return $this->moveToNextRoute();
            }
        }
        else {
            if($this->getLpa()->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                $form->bind($this->getLpa()->document->replacementAttorneyDecisions->flatten());
            }
        }

        return new ViewModel(['form'=>$form]);
    }
}
