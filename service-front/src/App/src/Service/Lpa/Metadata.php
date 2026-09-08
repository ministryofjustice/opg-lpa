<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use MakeShared\DataModel\Lpa\Lpa;
use Psr\Log\LoggerInterface;

class Metadata
{
    public function __construct(
        private readonly Application $lpaApplicationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setReplacementAttorneysConfirmed(Lpa $lpa, int $ifMatchVersion): int
    {
        return $this->setMetadataByKey($lpa, Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED, true, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    public function setCertificateProviderSkipped(Lpa $lpa, int $ifMatchVersion): int
    {
        if ($this->setMetadataByKey($lpa, Lpa::CERTIFICATE_PROVIDER_WAS_SKIPPED, true, $ifMatchVersion)) {
            $ifMatchVersion++;
        }

        return $this->setMetadataByKey($lpa, Lpa::CERTIFICATE_PROVIDER_SKIPPED, true, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    public function setPeopleToNotifyConfirmed(Lpa $lpa, int $ifMatchVersion): int
    {
        return $this->setMetadataByKey($lpa, Lpa::PEOPLE_TO_NOTIFY_CONFIRMED, true, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    public function setRepeatApplicationConfirmed(Lpa $lpa, int $ifMatchVersion): int
    {
        return $this->setMetadataByKey($lpa, Lpa::REPEAT_APPLICATION_CONFIRMED, true, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    public function setInstructionConfirmed(Lpa $lpa, int $ifMatchVersion): int
    {
        return $this->setMetadataByKey($lpa, Lpa::INSTRUCTION_CONFIRMED, true, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    /**
     * @psalm-param 10 $returnCount
     */
    public function setAnalyticsReturnCount(Lpa $lpa, int $returnCount, int $ifMatchVersion): int
    {
        return $this->setMetadataByKey($lpa, Lpa::ANALYTICS_RETURN_COUNT, $returnCount, $ifMatchVersion) ? $ifMatchVersion + 1 : $ifMatchVersion;
    }

    public function removeMetadata(Lpa $lpa, string $key, int $ifMatchVersion): bool
    {
        $metaData = $lpa->getMetadata();
        if (array_key_exists($key, $metaData)) {
            //  Remove the value
            unset($metaData[$key]);
            $lpa->setMetadata($metaData);

            $this->setMetaData($lpa->getId(), $lpa->getMetadata(), $ifMatchVersion);

            return true;
        }

        return false;
    }

    /**
     * Sets the LPA's metadata
     */
    private function setMetaData(int|string $lpaId, array $metadata, int $ifMatchVersion): void
    {
        $this->lpaApplicationService->updateApplication($lpaId, [
            'metadata' => $metadata
        ], $ifMatchVersion);
    }

    private function setMetadataByKey(Lpa $lpa, string $key, mixed $value, int $ifMatchVersion): bool
    {
        $metaData = $lpa->getMetadata();
        if (!array_key_exists($key, $metaData) || $metaData[$key] !== $value) {
            //  Update the value
            $metaData[$key] = $value;
            $lpa->setMetadata($metaData);

            $this->setMetaData($lpa->getId(), $lpa->getMetadata(), $ifMatchVersion);

            return true;
        }

        return false;
    }
}
