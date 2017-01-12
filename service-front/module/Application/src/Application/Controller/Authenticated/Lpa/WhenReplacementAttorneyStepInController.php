<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInController extends AbstractLpaController
{
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\WhenReplacementAttorneyStepInForm');

        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            if($postData['when'] != ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                $form->setValidationGroup(
                        'when'
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

                $whenReplacementStepIn = $form->getData()['when'];

                if($whenReplacementStepIn == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $whenDetails = $form->getData()['whenDetails'];
                }
                else {
                    $whenDetails = null;
                }

                if(($decisions->when !== $whenReplacementStepIn) || ($decisions->whenDetails !== $whenDetails)) {
                    $decisions->when = $whenReplacementStepIn;
                    $decisions->whenDetails = $whenDetails;

                    // persist data
                    if(!$this->getLpaApplicationService()->setReplacementAttorneyDecisions($lpaId, $decisions)) {
                        throw new \RuntimeException('API client failed to set replacement step in decisions for id: '.$lpaId);
                    }
                }

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
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
