<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Lpa;

class Applicant
{
    private Application $lpaApplicationService;

    /**
     * @psalm-param 222|444 $attorneyId
     */
    public function removeAttorney(Lpa $lpa, int $attorneyId): void
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

    public function cleanUp(Lpa $lpa): void
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

    public function setLpaApplicationService(Application $lpaApplicationService): void
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
