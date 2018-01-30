<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;

/**
 * Used for setting metadata constants etc.
 *
 * Class Metadata
 * @package Application\Model\Service\Lpa
 */
class Metadata
{
    public function setReplacementAttorneysConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED);
    }

    public function setCertificateProviderSkipped(Lpa $lpa)
    {
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

    public function setApplyForFeeReduction(Lpa $lpa, $applyOrNot)
    {
        return $this->setMetadataByKey($lpa, Lpa::APPLY_FOR_FEE_REDUCTION, $applyOrNot);
    }

    public function removeMetadata(Lpa $lpa, $key)
    {
        if (array_key_exists($key, $lpa->metadata)) {
            //  Remove the value
            unset($lpa->metadata[$key]);

            if (!$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata)) {
                throw new RuntimeException(sprintf('API client failed to remove metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }

    private function setMetadataByKey(Lpa $lpa, $key, $value = true)
    {
        if (!array_key_exists($key, $lpa->metadata) || $lpa->metadata[$key] !== $value) {
            //  Update the value
            $lpa->metadata[$key] = $value;

            if (!$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata)) {
                throw new RuntimeException(sprintf('API client failed to set metadata %s for id: %s in %s', $key, $lpa->id, __METHOD__));
            }

            return true;
        }

        return false;
    }
}
