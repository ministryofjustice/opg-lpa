<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Laminas\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\WhenReplacementAttorneyStepInForm', [
                         'lpa' => $lpa,
                     ]);

        $replacementAttorneyDecisions = $lpa->document->replacementAttorneyDecisions;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();

            if ($postData['when'] != ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                $form->setValidationGroup(['when']);
            }

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                if (!$replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                    $replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
                    $lpa->document->replacementAttorneyDecisions = $replacementAttorneyDecisions;
                }

                $whenReplacementStepIn = $form->getData()['when'];
                $whenDetails = null;

                if ($whenReplacementStepIn == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $whenDetails = $form->getData()['whenDetails'];
                }

                if (
                    $replacementAttorneyDecisions->when !== $whenReplacementStepIn ||
                    $replacementAttorneyDecisions->whenDetails !== $whenDetails
                ) {
                    $replacementAttorneyDecisions->when = $whenReplacementStepIn;
                    $replacementAttorneyDecisions->whenDetails = $whenDetails;

                    // persist data
                    $setOk = $this->getLpaApplicationService()->setReplacementAttorneyDecisions(
                        $lpa,
                        $replacementAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new \RuntimeException(
                            'API client failed to set replacement step in decisions for id: ' . $lpa->id
                        );
                    }
                }

                $this->cleanUpReplacementAttorneyDecisions();

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
