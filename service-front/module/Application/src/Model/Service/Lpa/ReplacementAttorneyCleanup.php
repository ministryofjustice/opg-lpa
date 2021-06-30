<?php

namespace Application\Model\Service\Lpa;

use Application\Model\FormFlowChecker;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class ReplacementAttorneyCleanup extends AbstractService
{
    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * Cleanup data to to with how the Replacement Attorneys should act
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    public function cleanUp(Lpa $lpa): void
    {
        if ($this->whenDecisionsInvalid($lpa)) {
            $lpa->getDocument()->getReplacementAttorneyDecisions()->setWhen(null);
            $lpa->getDocument()->getReplacementAttorneyDecisions()->setWhenDetails(null);

            $this->lpaApplicationService
                ->setReplacementAttorneyDecisions($lpa, $lpa->getDocument()->getReplacementAttorneyDecisions());
        }

        if ($this->howDecisionsInvalid($lpa)) {
            $lpa->getDocument()->getReplacementAttorneyDecisions()->setHow(null);
            $lpa->getDocument()->getReplacementAttorneyDecisions()->setHowDetails(null);

            $this->lpaApplicationService
                ->setReplacementAttorneyDecisions($lpa, $lpa->getDocument()->getReplacementAttorneyDecisions());
        }
    }

    /**
     * @param Lpa $lpa
     * @return bool
     */
    private function whenDecisionsInvalid(Lpa $lpa)
    {
        $decisions = $lpa->getDocument()->getReplacementAttorneyDecisions();

        $formFlowChecker = new FormFlowChecker($lpa);

        // there are some decisions to remove
        if ($decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->getWhen()) || !empty($decisions->getWhenDetails()))) {
            if (!$formFlowChecker->lpaHasReplacementAttorney()
               || !$lpa->hasMultiplePrimaryAttorneys()
               || !$lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Lpa $lpa
     * @return bool
     */
    private function howDecisionsInvalid(Lpa $lpa)
    {
        $decisions = $lpa->getDocument()->getReplacementAttorneyDecisions();

        $formFlowChecker = new FormFlowChecker($lpa);

        // there are some decisions to remove
        if ($decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->getHow()) || !empty($decisions->getHowDetails()))) {
            if (!$lpa->hasMultipleReplacementAttorneys()) {
                return true;
            }

            if (!(count($lpa->getDocument()->getPrimaryAttorneys()) == 1) &&
                !($formFlowChecker->isLpaWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()) &&
                !($lpa->hasMultiplePrimaryAttorneys() && $lpa->isHowPrimaryAttorneysMakeDecisionJointly())) {
                return true;
            }
        }

        return false;
    }

    public function setLpaApplicationService(LpaApplicationService $lpaApplicationService): void
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
