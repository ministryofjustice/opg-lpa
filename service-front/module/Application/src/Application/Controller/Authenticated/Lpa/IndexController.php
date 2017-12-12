<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

class IndexController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $seedId = $lpa->seed;

        if ($seedId) {
            $this->resetSessionCloneData($seedId);
        }

        // We want to track the number of times an LPA has been 'worked on'.
        // Which is defined by the number of times this method is called, per LPA.

        // Assume it's the first return...
        $analyticsReturnCount = 0;

        // but update the values if it's not the first time...
        if (isset($lpa->metadata['analyticsReturnCount'])) {
            $analyticsReturnCount = $lpa->metadata['analyticsReturnCount'];
        }

        //  Increment the analytics return count
        $analyticsReturnCount++;

        $this->getLpaApplicationService()->setMetaData($lpa->id, [
            'analyticsReturnCount' => $analyticsReturnCount
        ]);

        $destinationRoute = $this->getFlowChecker()->backToForm();

        return $this->redirect()->toRoute($destinationRoute, ['lpa-id' => $lpa->id]);
    }
}
