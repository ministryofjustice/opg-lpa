<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class HowPrimaryAttorneysMakeDecisionController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    /** @var ApplicantService */
    private $applicantService;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\HowAttorneysMakeDecisionForm', [
                         'lpa' => $lpa,
                     ]);

        // There will be some primary attorney descisions at this
        // point because they will have been created in an earlier step
        $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();

            if ($postData['how'] != PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(['how']);
            }

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                $howAttorneysAct = $form->getData()['how'];
                $howDetails = null;

                if ($howAttorneysAct == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $form->getData()['howDetails'];
                }

                if (
                    $primaryAttorneyDecisions->how !== $howAttorneysAct ||
                    $primaryAttorneyDecisions->howDetails !== $howDetails
                ) {
                    $primaryAttorneyDecisions->how = $howAttorneysAct;
                    $primaryAttorneyDecisions->howDetails = $howDetails;

                    // persist data
                    $setOk = $this->getLpaApplicationService()->setPrimaryAttorneyDecisions(
                        $lpa,
                        $primaryAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set primary attorney decisions for id: ' . $lpa->id
                        );
                    }

                    $this->cleanUpReplacementAttorneyDecisions();

                    $this->applicantService->cleanUp($lpa);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $form->bind($primaryAttorneyDecisions->flatten());
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function setApplicantService(ApplicantService $applicantService)
    {
        $this->applicantService = $applicantService;
    }
}
