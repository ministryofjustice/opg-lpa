<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use Application\View\StatusViewDataBuilder;
use DateTime;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

/**
 * Class StatusController
 * @package Application\Controller\Authenticated\Lpa
 */
class StatusController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        $viewData = null;

        $lpa = $this->getLpa();

        if ($lpa->getCompletedAt() instanceof DateTime) {
            $trackFromDate = null;
            if (isset($this->config()['processing-status']['track-from-date'])) {
                $trackFromDate = new DateTime($this->config()['processing-status']['track-from-date']);
            }

            $expectedWorkingDaysBeforeReceipt = null;
            if (isset($this->config()['processing-status']['expected-working-days-before-receipt'])) {
                $expectedWorkingDaysBeforeReceipt =
                    intval($this->config()['processing-status']['expected-working-days-before-receipt']);
            }

            $lpaStatusDetails = $this->getLpaApplicationService()->getStatuses($lpa->getId());

            $builder = new StatusViewDataBuilder();

            $viewData = $builder->build(
                $lpa,
                $lpaStatusDetails,
                $trackFromDate,
                $expectedWorkingDaysBeforeReceipt,
            );
        }

        if ($viewData === null) {
            return $this->redirectToRoute('user/dashboard');
        }

        return new ViewModel($viewData->toArray());
    }
}
