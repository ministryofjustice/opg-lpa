<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class ReplacementAttorneyCleanup extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * Cleanup data to to with how the Replacement Attorneys should act
     *
     * @param Lpa $lpa
     */
    public function cleanUp(Lpa $lpa)
    {
        $stateChecker = new StateChecker($lpa);

        if ($this->whenDecisionsInvalid($lpa, $stateChecker)) {
            $this->removeWhenDecisions($lpa);
        }

        if ($this->howDecisionsInvalid($lpa, $stateChecker)) {
            $this->removeHowDecisions($lpa);
        }
    }

    /**
     * @param Lpa $lpa
     * @param StateChecker $stateChecker
     * @return bool
     */
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

    /**
     * @param Lpa $lpa
     */
    private function removeWhenDecisions(Lpa $lpa)
    {
        $lpa->document->replacementAttorneyDecisions->when = null;
        $lpa->document->replacementAttorneyDecisions->whenDetails = null;

        $this->apiClient->setReplacementAttorneyDecisions($this->getUserId(), $lpa->id, $lpa->document->replacementAttorneyDecisions);
    }

    /**
     * @param Lpa $lpa
     * @param StateChecker $stateChecker
     * @return bool
     */
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

    /**
     * @param Lpa $lpa
     */
    private function removeHowDecisions(Lpa $lpa)
    {
        $lpa->document->replacementAttorneyDecisions->how = null;
        $lpa->document->replacementAttorneyDecisions->howDetails = null;

        $this->apiClient->setReplacementAttorneyDecisions($this->getUserId(), $lpa->id, $lpa->document->replacementAttorneyDecisions);
    }
}
