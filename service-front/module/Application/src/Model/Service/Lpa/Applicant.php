<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Lpa;

class Applicant
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
    ) {
    }

    /**
     * Remove an attorney from the applicants array
     *
     * @param Lpa $lpa
     * @param $attorneyId
     */
    public function removeAttorney(Lpa $lpa, $attorneyId)
    {
        $whoIsRegistering = $lpa->document->whoIsRegistering;

        if (is_array($whoIsRegistering) && in_array($attorneyId, $whoIsRegistering)) {
            foreach ($whoIsRegistering as $idx => $whoIsRegisteringId) {
                if ($whoIsRegisteringId == $attorneyId) {
                    unset($whoIsRegistering[$idx]);

                    if (count($whoIsRegistering) == 0) {
                        $whoIsRegistering = null;
                    }

                    $this->lpaApplicationService->setWhoIsRegistering($lpa, $whoIsRegistering);
                    break;
                }
            }
        }
    }

    /**
     * Cleanup applicant data (whoIsRegistering) if invalid
     *
     * @param Lpa $lpa
     */
    public function cleanUp(Lpa $lpa)
    {
        $applicants = $lpa->document->whoIsRegistering;

        //  Only do something if the applicants is an array value (attorneys)
        if (is_array($applicants)) {
            //  Rebuild the applicants array based on the current data so we can compare below
            $newApplicants = [];

            foreach ($lpa->document->primaryAttorneys as $primaryAttorney) {
                //  If decisions are being made jointly (where we will add all attorneys) or this attorney is present in the existing values
                if ($lpa->document->primaryAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY || in_array($primaryAttorney->id, $applicants)) {
                    $newApplicants[] = $primaryAttorney->id;
                }
            }

            if ($applicants !== $newApplicants) {
                $this->lpaApplicationService->setWhoIsRegistering($lpa, $newApplicants);
            }
        }
    }
}
