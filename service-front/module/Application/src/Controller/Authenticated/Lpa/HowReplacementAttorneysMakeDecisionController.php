<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class HowReplacementAttorneysMakeDecisionController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm', [
                         'lpa' => $lpa,
                     ]);

        $replacementAttorneyDecisions = $lpa->document->replacementAttorneyDecisions;

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            if ($postData['how'] != ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(array('how'));
            }

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                if (!$replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                    $replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
                    $lpa->document->replacementAttorneyDecisions = $replacementAttorneyDecisions;
                }

                $howAttorneysAct = $form->getData()['how'];
                $howDetails = null;

                if ($howAttorneysAct == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $form->getData()['howDetails'];
                }

                if ($replacementAttorneyDecisions->how !== $howAttorneysAct || $replacementAttorneyDecisions->howDetails !== $howDetails) {
                    $replacementAttorneyDecisions->how = $howAttorneysAct;
                    $replacementAttorneyDecisions->howDetails = $howDetails;

                    // persist data
                    if (!$this->getLpaApplicationService()->setReplacementAttorneyDecisions($lpa, $replacementAttorneyDecisions)) {
                        throw new RuntimeException('API client failed to set replacement attorney decisions for id: ' . $lpa->id);
                    }
                }

                return $this->moveToNextRoute();
            }
        } else {
            if ($replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                $form->bind($replacementAttorneyDecisions->flatten());
            }
        }

        return new ViewModel(['form' => $form]);
    }
}
