<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use DateInterval;
use Exception;
use Laminas\View\Model\ViewModel;
use DateTime;

/**
 * Class StatusController
 * @package Application\Controller\Authenticated\Lpa
 */
class StatusController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $lpaId = $lpa->getId();
        $lpaStatus = null;
        $returnUnpaid = false;

        if ($lpa->getCompletedAt() instanceof DateTime) {
            // A 'completed' status is used for LPA applications received before the track-from date;
            // if a better status can be determined below, we don't use 'completed'
            $lpaStatus = 'completed';

            $trackFromDate = new DateTime($this->config()['processing-status']['track-from-date']);

            if ($trackFromDate <= new DateTime('now') && $trackFromDate <= $lpa->getCompletedAt()) {
                $lpaStatus = 'waiting';

                $lpaStatusDetails = $this->getLpaApplicationService()->getStatuses($lpaId);

                if (array_key_exists($lpaId, $lpaStatusDetails) && $lpaStatusDetails[$lpaId]['found'] == true) {
                    $lpaStatus = strtolower($lpaStatusDetails[$lpaId]['status']);
                    $returnUnpaid = isset($lpaStatusDetails[$lpaId]['returnUnpaid']);
                }
            }
        }

        // Keep these statuses in workflow order
        $statuses = ['completed', 'waiting', 'received', 'checking', 'processed'];

        // Invalid status, redirect immediately
        if (!in_array($lpaStatus, $statuses)) {
            return $this->redirect()->toRoute('user/dashboard');
        }

        // Find all the statuses (inc. the current one) which have been done
        // for this LPA application
        $doneStatuses = array_slice($statuses, 0, array_search($lpaStatus, $statuses));

        // The metadata used here is stored in the db but is originally
        // populated from Sirius.
        $metadata = $lpa->getMetadata();

        // Return the rejected, invalid, withdrawn or dispatch date
        // (whichever is latest). NB dates are strings at this point.
        $processedDate = null;
        $dateFields = ['rejected', 'withdrawn', 'invalid', 'dispatch'];
        for ($i = 0; $i < count($dateFields); $i++) {
            $metadataField = 'application-' . $dateFields[$i] . '-date';
            if (isset($metadata[$metadataField])) {
                $dateString = $metadata[$metadataField];
                if (is_null($processedDate) || $dateString > $processedDate) {
                    $processedDate = $dateString;
                }
            }
        }

        // The "should receive by" date is set to a number of working days after the
        // $processedDate, defined in config
        $shouldReceiveByDate = null;
        if (
            !is_null($processedDate) &&
            isset($this->config()['processing-status']['expected-working-days-before-receipt'])
        ) {
            $days = intval($this->config()['processing-status']['expected-working-days-before-receipt']);
            $shouldReceiveByDate = new DateTime($processedDate);
            $interval = new DateInterval('P1D');

            $i = 1;
            while ($i <= $days) {
                $shouldReceiveByDate->add($interval);

                // count this day if the new $shouldReceiveByDate is a week day
                // (0 = Sunday, 6 = Saturday)
                $dayOfWeek = $shouldReceiveByDate->format('w');
                if ($dayOfWeek !== '0' && $dayOfWeek !== '6') {
                    $i++;
                }
            }
        }

        return new ViewModel([
            'lpa'                 => $lpa,
            'shouldReceiveByDate' => $shouldReceiveByDate,
            'returnUnpaid'        => $returnUnpaid,
            'status'              => $lpaStatus,
            'doneStatuses'        => $doneStatuses,
            'canGenerateLPA120'   => $lpa->canGenerateLPA120(),
        ]);
    }
}
