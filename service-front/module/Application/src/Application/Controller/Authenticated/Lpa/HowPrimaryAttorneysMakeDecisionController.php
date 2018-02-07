<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Zend\View\Model\ViewModel;
use RuntimeException;

class HowPrimaryAttorneysMakeDecisionController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm', [
                         'lpa' => $lpa,
                     ]);

        //  There will be some primary attorney descisions at this point because they will have been created in an earlier step
        $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions;

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            if ($postData['how'] != PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup('how');
            }

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                $howAttorneysAct = $form->getData()['how'];
                $howDetails = null;

                if ($howAttorneysAct == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $form->getData()['howDetails'];
                }

                if ($primaryAttorneyDecisions->how !== $howAttorneysAct || $primaryAttorneyDecisions->howDetails !== $howDetails) {
                    $primaryAttorneyDecisions->how = $howAttorneysAct;
                    $primaryAttorneyDecisions->howDetails = $howDetails;

                    // persist data
                    if (!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpa->id, $primaryAttorneyDecisions)) {
                        throw new RuntimeException('API client failed to set primary attorney decisions for id: ' . $lpa->id);
                    }

                    $this->cleanUpReplacementAttorneyDecisions();
                    $this->cleanUpApplicant();
                }

                return $this->moveToNextRoute();
            }
        } else {
            $form->bind($primaryAttorneyDecisions->flatten());
        }

        return new ViewModel(['form' => $form]);
    }
}
