<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class HowReplacementAttorneysMakeDecisionController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm', [
                         'lpa' => $lpa,
                     ]);

        $replacementAttorneyDecisions = $lpa->document->replacementAttorneyDecisions;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();

            if ($postData['how'] != ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(['how']);
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

                if (
                    $replacementAttorneyDecisions->how !== $howAttorneysAct ||
                    $replacementAttorneyDecisions->howDetails !== $howDetails
                ) {
                    $replacementAttorneyDecisions->how = $howAttorneysAct;
                    $replacementAttorneyDecisions->howDetails = $howDetails;

                    // persist data
                    $setOk = $this->getLpaApplicationService()->setReplacementAttorneyDecisions(
                        $lpa,
                        $replacementAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set replacement attorney decisions for id: ' . $lpa->id
                        );
                    }
                }

                return $this->moveToNextRoute();
            }
        } elseif ($replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
            $form->bind($replacementAttorneyDecisions->flatten());
        }

        return new ViewModel(['form' => $form]);
    }
}
