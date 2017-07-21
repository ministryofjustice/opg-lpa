<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ApplicantCleanup
{
    /**
     * Cleanup applicant data (whoIsRegistering) if invalid
     *
     * @param Lpa $lpa
     * @param $client
     */
    public function cleanUp(Lpa $lpa, $client)
    {
        if ($this->whenApplicantInvalid($lpa)) {
            $client->deleteWhoIsRegistering($lpa->id);
        }
    }

    /**
     * @param Lpa $lpa
     * @return bool
     */
    protected function whenApplicantInvalid(Lpa $lpa)
    {
        //Applicant is only suspicious when it's an array as that means it's one or more of the primary attorneys
        if ($lpa->document !== null && is_array($lpa->document->whoIsRegistering)) {
            $primaryAttorneys = $lpa->document->primaryAttorneys;
            $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;
            $whoIsRegistering = $lpa->document->whoIsRegistering;

            $noPrimaryAttorneys = count($primaryAttorneys);
            $noApplicants = count($whoIsRegistering);

            //More applicants than attorneys is always invalid
            if ($noApplicants > $noPrimaryAttorneys) {
                return true;
            }

            //If primary attorneys make decisions jointly, all must be applicants
            if ($primaryAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY && $noApplicants !== $noPrimaryAttorneys) {
                return true;
            }

            //Verify all applicant ids are valid
            $allApplicantIdsValid = true;
            foreach ($whoIsRegistering as $id) {
                foreach ($primaryAttorneys as $primaryAttorney) {
                    if (!$allApplicantIdsValid) {
                        break;
                    }

                    $allApplicantIdsValid = false;
                    if ($id == $primaryAttorney->id) {
                        $allApplicantIdsValid = true;
                        continue;
                    }
                }
            }

            if (!$allApplicantIdsValid) {
                return true;
            }
        }

        return false;
    }
}
