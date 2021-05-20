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
        $lpaStatus = null;

        if ($lpa->getCompletedAt() instanceof DateTime) {
            $lpaStatus = 'completed';

            $trackFromDate = new DateTime($this->config()['processing-status']['track-from-date']);

            if ($trackFromDate <= new DateTime('now') && $trackFromDate <= $lpa->getCompletedAt()) {
                $lpaStatus = 'waiting';

                $lpaStatusDetails = $this->getLpaApplicationService()->getStatuses($lpa->getId());

                if ($lpaStatusDetails[$lpa->getId()]['found'] == true) {
                    $lpaStatus = strtolower($lpaStatusDetails[$lpa->getId()]['status']);
                }
            }
        }

        //  Keep these statues in workflow order
        $statuses = ['completed', 'waiting', 'received', 'checking', 'processed'];
        if (!in_array($lpaStatus, $statuses)) {
            return $this->redirect()->toRoute('user/dashboard');
        }

        //  Determine what statuses should trigger the current status to display as 'done'
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
        if (!is_null($processedDate) && isset($this->config()['processing-status']['expected-working-days-before-receipt'])) {
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
            'status'              => $lpaStatus,
            'doneStatuses'        => $doneStatuses,
            'canGenerateLPA120'   => $lpa->canGenerateLPA120(),
        ]);
    }
}
