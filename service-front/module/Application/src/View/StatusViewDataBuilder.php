<?php

declare(strict_types=1);

namespace Application\View;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use MakeShared\DataModel\Lpa\Lpa;

class StatusViewDataBuilder
{
    /**
     * @param array $lpaStatusDetails Map from LPA IDs to Sirius responses about their current status.
     */
    public function build(
        Lpa $lpa,
        array $lpaStatusDetails,
        ?DateTimeInterface $trackFromDate,
        ?int $expectedWorkingDaysBeforeReceipt,
    ): ?StatusViewData {
        $lpaId = $lpa->getId();
        $metadata = $lpa->getMetadata();

        $returnUnpaid = false;

        $lpaStatus = 'completed';

        if (
            $trackFromDate !== null
            && $trackFromDate <= new DateTimeImmutable('now')
            && $lpa->getCompletedAt() instanceof DateTimeInterface
            && $trackFromDate <= $lpa->getCompletedAt()
        ) {
            $lpaStatus = 'waiting';

            if (
                array_key_exists($lpaId, $lpaStatusDetails)
                && (($lpaStatusDetails[$lpaId]['found'] ?? false) === true)
            ) {
                $lpaStatus = strtolower((string) ($lpaStatusDetails[$lpaId]['status'] ?? ''));
                $returnUnpaid = (bool) ($lpaStatusDetails[$lpaId]['returnUnpaid'] ?? false);
            } else {
                if (isset($metadata[Lpa::SIRIUS_PROCESSING_STATUS])) {
                    $lpaStatus = strtolower((string) $metadata[Lpa::SIRIUS_PROCESSING_STATUS]);
                }
                if (isset($metadata['application-return-unpaid'])) {
                    $returnUnpaid = (bool) $metadata['application-return-unpaid'];
                }
            }
        }

        $statuses = ['completed', 'waiting', 'received', 'checking', 'processed'];

        if (!in_array($lpaStatus, $statuses, true)) {
            return null;
        }

        $doneStatuses = array_slice($statuses, 0, (int) array_search($lpaStatus, $statuses, true));

        $processedDate = $this->findLatestProcessedDateString($metadata);

        $shouldReceiveByDate = null;
        if ($processedDate !== null && $expectedWorkingDaysBeforeReceipt !== null) {
            $shouldReceiveByDate = $this->addWorkingDays(
                new DateTimeImmutable($processedDate),
                $expectedWorkingDaysBeforeReceipt,
            );
        }

        return new StatusViewData(
            lpa: $lpa,
            shouldReceiveByDate: $shouldReceiveByDate,
            returnUnpaid: $returnUnpaid,
            status: $lpaStatus,
            doneStatuses: $doneStatuses,
            canGenerateLPA120: $lpa->canGenerateLPA120(),
        );
    }

    private function findLatestProcessedDateString(array $metadata): ?string
    {
        $processedDate = null;

        foreach (['rejected', 'withdrawn', 'invalid', 'dispatch'] as $dateField) {
            $metadataField = 'application-' . $dateField . '-date';

            if (!isset($metadata[$metadataField])) {
                continue;
            }

            $dateString = (string) $metadata[$metadataField];

            if ($processedDate === null || $dateString > $processedDate) {
                $processedDate = $dateString;
            }
        }

        return $processedDate;
    }

    private function addWorkingDays(DateTimeImmutable $from, int $workingDays): DateTimeImmutable
    {
        $date = $from;
        $interval = new DateInterval('P1D');

        $i = 1;
        while ($i <= $workingDays) {
            $date = $date->add($interval);

            $dayOfWeek = $date->format('w');
            // (0 = Sunday, 6 = Saturday)
            if ($dayOfWeek !== '0' && $dayOfWeek !== '6') {
                $i++;
            }
        }

        return $date;
    }
}
