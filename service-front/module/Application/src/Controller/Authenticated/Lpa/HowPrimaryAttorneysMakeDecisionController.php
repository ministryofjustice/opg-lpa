<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class HowPrimaryAttorneysMakeDecisionController extends AbstractLpaController
{
    /**
     * @var ApplicantService
     */
    private $applicantService;

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
                $form->setValidationGroup(array('how'));
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
                    if (!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpa, $primaryAttorneyDecisions)) {
                        throw new RuntimeException('API client failed to set primary attorney decisions for id: ' . $lpa->id);
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
