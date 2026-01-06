<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use MakeShared\Logging\LoggerTrait;

class IndexController extends AbstractLpaController
{
    use LoggerTrait;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $seedId = (string) $lpa->seed;

        if ($seedId) {
            $this->resetSessionCloneData($seedId);
        }

        // We want to track the number of times an LPA has been 'worked on'.
        // Which is defined by the number of times this method is called, per LPA.

        //  Get the current count and increment by 1
        $analyticsReturnCount = (isset($lpa->metadata['analyticsReturnCount']) ? $lpa->metadata['analyticsReturnCount'] : 0);
        $analyticsReturnCount++;

        $this->getMetadata()->setAnalyticsReturnCount($lpa, $analyticsReturnCount);

        $destinationRoute = $this->getFlowChecker()->backToForm();

        return $this->redirect()->toRoute($destinationRoute, ['lpa-id' => $lpa->id], $this->getFlowChecker()->getRouteOptions($destinationRoute));
    }
}
