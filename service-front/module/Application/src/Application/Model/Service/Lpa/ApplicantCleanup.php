<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class ApplicantCleanup
{
    /**
     * @param Lpa $lpa
     * @return bool
     */
    protected function whenApplicantInvalid(Lpa $lpa)
    {
        return false;
    }

    /**
     * Cleanup data to to with how the Replacement Attorneys should act
     *
     * @param Lpa $lpa
     */
    public function cleanUp(Lpa $lpa, $client)
    {
        $stateChecker = new StateChecker($lpa);
        if ($this->whenDecisionsInvalid($lpa, $stateChecker)) {
            $this->removeWhenDecisions($lpa, $client);
        }

        if ($this->howDecisionsInvalid($lpa, $stateChecker)) {
            $this->removeHowDecisions($lpa, $client);
        }
    }

    private function whenDecisionsInvalid(Lpa $lpa, StateChecker $stateChecker)
    {
        $decisions = $lpa->document->replacementAttorneyDecisions;
        // there are some decisions to remove
        if ($decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->when) || !empty($decisions->whenDetails))) {

            if (!$stateChecker->lpaHasReplacementAttorney()
               || !$stateChecker->lpaHasMultiplePrimaryAttorneys()
               || !$stateChecker->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                return true;
            }
        }
        return false;
    }

    private function removeWhenDecisions(Lpa $lpa, $client)
    {
        $lpa->document->replacementAttorneyDecisions->when = null;
        $lpa->document->replacementAttorneyDecisions->whenDetails = null;
        $client->setReplacementAttorneyDecisions($lpa->id, $lpa->document->replacementAttorneyDecisions);
    }

    private function howDecisionsInvalid(Lpa $lpa, StateChecker $stateChecker)
    {
        $decisions = $lpa->document->replacementAttorneyDecisions;
        // there are some decisions to remove
        if ($decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->how) || !empty($decisions->howDetails))) {

            if (!$stateChecker->lpaHasMultipleReplacementAttorneys()) {
                return true;
            }

            if (!(count($lpa->document->primaryAttorneys) == 1) &&
                !($stateChecker->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()) &&
                !($stateChecker->lpaPrimaryAttorneysMakeDecisionJointly())) {
                return true;
            }
        }
        return false;
    }

    private function removeHowDecisions(Lpa $lpa, $client)
    {
        $lpa->document->replacementAttorneyDecisions->how = null;
        $lpa->document->replacementAttorneyDecisions->howDetails = null;
        $client->setReplacementAttorneyDecisions($lpa->id, $lpa->document->replacementAttorneyDecisions);
    }
}
