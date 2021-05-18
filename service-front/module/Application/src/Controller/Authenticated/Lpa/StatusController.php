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

        // Return the rejected, invalid, withdrawn or registration date.
        // The metadata used here is stored in the db but is originally
        // populated from Sirius.
        $metadata = $lpa->getMetadata();

        $processedDate = null;
        if (isset($metadata['application-rejected-date']))
            $processedDate = $metadata['application-rejected-date'];
        else if (isset($metadata['application-withdrawn-date']))
            $processedDate = $metadata['application-withdrawn-date'];
        else if (isset($metadata['application-invalid-date']))
            $processedDate = $metadata['application-invalid-date'];
        else if (isset($metadata['application-dispatch-date']))
            $processedDate = $metadata['application-dispatch-date'];

        // The "should receive by" date is set to a number of days after the
        // $processedDate, defined in config
        $shouldReceiveByDate = null;
        if (!is_null($processedDate) && isset($this->config()['processing-status']['expected-days-before-receipt'])) {
            $days = intval($this->config()['processing-status']['expected-days-before-receipt']);
            $interval = new DateInterval("P${days}D");
            try {
                $shouldReceiveByDate = (new DateTime($processedDate))->add($interval);
            } catch (Exception $e) {
                $this->getLogger()->err('Error calculating expected receipt date: ' . $e->getMessage());
            }
        }

        return new ViewModel([
            'processedDate'       => $processedDate,
            'lpa'                 => $lpa,
            'shouldReceiveByDate' => $shouldReceiveByDate,
            'status'              => $lpaStatus,
            'doneStatuses'        => $doneStatuses,
            'canGenerateLPA120'   => $lpa->canGenerateLPA120(),
        ]);
    }
}
