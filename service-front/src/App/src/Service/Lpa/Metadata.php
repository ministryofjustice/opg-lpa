<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

class Metadata implements LoggerAwareInterface
{
    use LoggerTrait;

    private Application $lpaApplicationService;

    public function setReplacementAttorneysConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED);
    }

    public function setCertificateProviderSkipped(Lpa $lpa)
    {
        $this->setMetadataByKey($lpa, Lpa::CERTIFICATE_PROVIDER_WAS_SKIPPED);

        return $this->setMetadataByKey($lpa, Lpa::CERTIFICATE_PROVIDER_SKIPPED);
    }

    public function setPeopleToNotifyConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, Lpa::PEOPLE_TO_NOTIFY_CONFIRMED);
    }

    public function setRepeatApplicationConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, Lpa::REPEAT_APPLICATION_CONFIRMED);
    }

    public function setInstructionConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, Lpa::INSTRUCTION_CONFIRMED);
    }

    /**
     * @psalm-param 10 $returnCount
     */
    public function setAnalyticsReturnCount(Lpa $lpa, int $returnCount)
    {
        return $this->setMetadataByKey($lpa, Lpa::ANALYTICS_RETURN_COUNT, $returnCount);
    }

    public function removeMetadata(Lpa $lpa, string $key): bool
    {
        if (array_key_exists($key, $lpa->metadata)) {
            //  Remove the value
            unset($lpa->metadata[$key]);

            if (!$this->setMetaData($lpa->id, $lpa->metadata)) {
                $this->getLogger()->warning('API client failed to remove metadata', [
                    'lpaId' => $lpa->id,
                    'status' => 500,
                ]);
                throw new RuntimeException(sprintf('API client failed to remove metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }

    /**
     * Sets the LPA's metadata
     */
    private function setMetaData(int|string $lpaId, array $metadata): bool
    {
        $this->lpaApplicationService->updateApplication($lpaId, [
            'metadata' => $metadata
        ]);

        return true;
    }

    private function setMetadataByKey(Lpa $lpa, string $key, $value = true): bool
    {
        if (!array_key_exists($key, $lpa->metadata) || $lpa->metadata[$key] !== $value) {
            //  Update the value
            $lpa->metadata[$key] = $value;

            if (!$this->setMetaData($lpa->id, $lpa->metadata)) {
                $this->getLogger()->warning('API client failed to remove metadata by key', [
                    'lpaId' => $lpa->id,
                    'status' => 500,
                ]);
                throw new RuntimeException(sprintf('API client failed to set metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }

    public function setLpaApplicationService(Application $lpaApplicationService): void
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
