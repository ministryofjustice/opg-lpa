<?php

namespace Application\View;

use DateInterval;
use DateTime;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Lpa;

class StatusViewModelHelper
{
    /**
     * Convert LPA and related status data into a ViewModel for use by StatusController.
     *
     * @param Lpa $lpa LPA we are building the status detail view model for
     * @param array $lpaStatusDetails Map from LPA IDs to Sirius responses about their current status in format
     *     {
     *         "<id>": {
     *             "status": null|"waiting"|"received"|"checking"|"processed"
     *             "returnUnpaid": true|false,
     *             "found": true|false
     *         }
     *     }
     * @param ?DateTime $trackFromDate Date from which tracking data for LPAs is available
     * @param ?int $expectedWorkingDaysBeforeReceipt Number of working days after the processing
     *     date for an LPA when the client can expect to receive the returned LPA in the post
     *
     * @return ?ViewModel ViewModel if status is valid, or null if not
     */
    public static function build(
        Lpa $lpa,
        array $lpaStatusDetails,
        ?DateTime $trackFromDate,
        ?int $expectedWorkingDaysBeforeReceipt,
    ): ?ViewModel {
        $lpaId = $lpa->getId();

        // The metadata used here is stored in the db but is originally
        // populated from Sirius.
        $metadata = $lpa->getMetadata();

        $returnUnpaid = false;

        // A 'completed' status is used for LPA applications received before the track-from date;
        // if a better status can be determined below, we won't use 'completed'
        $lpaStatus = 'completed';

        if (
            !is_null($trackFromDate) &&
            $trackFromDate <= new DateTime('now') &&
            $trackFromDate <= $lpa->getCompletedAt()
        ) {
            // Assume waiting status unless we get contrary evidence from Sirius
            $lpaStatus = 'waiting';

            if (array_key_exists($lpaId, $lpaStatusDetails) && $lpaStatusDetails[$lpaId]['found'] == true) {
                $lpaStatus = strtolower($lpaStatusDetails[$lpaId]['status']);
                $returnUnpaid = isset($lpaStatusDetails[$lpaId]['returnUnpaid']);
            } else {
                // Fall back to the cached metadata from the db (which was delivered to
                // us via service-api before being saved to db) if we couldn't get a status
                if (isset($metadata[Lpa::SIRIUS_PROCESSING_STATUS])) {
                    $lpaStatus = strtolower($metadata[Lpa::SIRIUS_PROCESSING_STATUS]);
                }
                if (isset($metadata['application-return-unpaid'])) {
                    $returnUnpaid = $metadata['application-return-unpaid'];
                }
            }
        }

        // Keep these statuses in workflow order
        $statuses = ['completed', 'waiting', 'received', 'checking', 'processed'];

        // Invalid status from Sirius, redirect immediately
        if (!in_array($lpaStatus, $statuses)) {
            return null;
        }

        // Find all the steps (inc. the current one) which have been done
        // for this LPA application
        $doneStatuses = array_slice($statuses, 0, array_search($lpaStatus, $statuses));

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
        if (!is_null($processedDate) && !is_null($expectedWorkingDaysBeforeReceipt)) {
            $shouldReceiveByDate = new DateTime($processedDate);
            $interval = new DateInterval('P1D');

            $i = 1;
            while ($i <= $expectedWorkingDaysBeforeReceipt) {
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
