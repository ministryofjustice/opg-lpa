<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Http\Response;
use MakeShared\DataModel\Lpa\Lpa;
use RuntimeException;

/**
 * Used for setting metadata constants etc.
 *
 * Class Metadata
 * @package Application\Model\Service\Lpa
 */
class Metadata extends AbstractService
{
    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

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

    public function setAnalyticsReturnCount(Lpa $lpa, $returnCount)
    {
        return $this->setMetadataByKey($lpa, Lpa::ANALYTICS_RETURN_COUNT, $returnCount);
    }

    public function removeMetadata(Lpa $lpa, $key)
    {
        if (array_key_exists($key, $lpa->metadata)) {
            //  Remove the value
            unset($lpa->metadata[$key]);

            if (!$this->setMetaData($lpa->id, $lpa->metadata)) {
                $this->getLogger()->warning('API client failed to remove metadata', [
                    'error_code' => 'API_CLIENT_METADATA_REMOVE_FAILED',
                    'lpaId' => $lpa->id,
                    'status' => Response::STATUS_CODE_500,
                ]);
                throw new RuntimeException(sprintf('API client failed to remove metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }

    /**
     * Sets the LPA's metadata
     *
     * @param string $lpaId
     * @param array $metadata
     * @return boolean
     */
    private function setMetaData($lpaId, array $metadata)
    {
        $this->lpaApplicationService->updateApplication($lpaId, [
            'metadata' => $metadata
        ]);

        return true;
    }

    private function setMetadataByKey(Lpa $lpa, $key, $value = true)
    {
        if (!array_key_exists($key, $lpa->metadata) || $lpa->metadata[$key] !== $value) {
            //  Update the value
            $lpa->metadata[$key] = $value;

            if (!$this->setMetaData($lpa->id, $lpa->metadata)) {
                $this->getLogger()->warning('API client failed to remove metadata by key', [
                    'error_code' => 'API_CLIENT_METADATA_REMOVE_FAILED',
                    'lpaId' => $lpa->id,
                    'status' => Response::STATUS_CODE_500,
                ]);
                throw new RuntimeException(sprintf('API client failed to set metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }

    public function setLpaApplicationService(LpaApplicationService $lpaApplicationService)
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
