<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class ReplacementAttorneyCleanup
{
    private Application $lpaApplicationService;

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

    private function whenDecisionsInvalid(Lpa $lpa): bool
    {
        $decisions = $lpa->getDocument()->getReplacementAttorneyDecisions();

        $formFlowChecker = new FormFlowChecker($lpa);

        // there are some decisions to remove
        if (
            $decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->getWhen()) || !empty($decisions->getWhenDetails()))
        ) {
            if (
                !$formFlowChecker->lpaHasReplacementAttorney()
                || !$lpa->hasMultiplePrimaryAttorneys()
                || !$lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()
            ) {
                return true;
            }
        }

        return false;
    }

    private function howDecisionsInvalid(Lpa $lpa): bool
    {
        $decisions = $lpa->getDocument()->getReplacementAttorneyDecisions();

        $formFlowChecker = new FormFlowChecker($lpa);

        // there are some decisions to remove
        if (
            $decisions instanceof ReplacementAttorneyDecisions &&
            (!empty($decisions->getHow()) || !empty($decisions->getHowDetails()))
        ) {
            if (!$lpa->hasMultipleReplacementAttorneys()) {
                return true;
            }

            if (
                !(count($lpa->getDocument()->getPrimaryAttorneys()) == 1) &&
                !($formFlowChecker->isLpaWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()) &&
                !($lpa->hasMultiplePrimaryAttorneys() && $lpa->isHowPrimaryAttorneysMakeDecisionJointly())
            ) {
                return true;
            }
        }

        return false;
    }

    public function setLpaApplicationService(Application $lpaApplicationService): void
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
