<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use RuntimeException;

/**
 * Used for setting metadata constants etc.
 *
 * Class Metadata
 * @package Application\Model\Service\Lpa
 */
class Metadata implements ServiceLocatorAwareInterface
{
    const REPLACEMENT_ATTORNEYS_CONFIRMED = 'replacement-attorneys-confirmed';
    const CERTIFICATE_PROVIDER_SKIPPED = 'certificate-provider-skipped';
    const PEOPLE_TO_NOTIFY_CONFIRMED = 'people-to-notify-confirmed';
    const REPEAT_APPLICATION_CONFIRMED = 'repeat-application-confirmed';
    const APPLY_FOR_FEE_REDUCTION = 'apply-for-fee-reduction';
    const INSTRUCTION_CONFIRMED = 'instruction-confirmed';
    const ANALYTICS_RETURN_COUNT = 'analyticsReturnCount';

    use ServiceLocatorAwareTrait;

    public function setReplacementAttorneysConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, self::REPLACEMENT_ATTORNEYS_CONFIRMED);
    }

    public function setCertificateProviderSkipped(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, self::CERTIFICATE_PROVIDER_SKIPPED);
    }

    public function setPeopleToNotifyConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, self::PEOPLE_TO_NOTIFY_CONFIRMED);
    }

    public function setRepeatApplicationConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, self::REPEAT_APPLICATION_CONFIRMED);
    }

    public function setInstructionConfirmed(Lpa $lpa)
    {
        return $this->setMetadataByKey($lpa, self::INSTRUCTION_CONFIRMED);
    }

    public function setAnalyticsReturnCount(Lpa $lpa, $returnCount)
    {
        return $this->setMetadataByKey($lpa, self::ANALYTICS_RETURN_COUNT, $returnCount);
    }

    public function setApplyForFeeReduction(Lpa $lpa, $applyOrNot)
    {
        return $this->setMetadataByKey($lpa, self::APPLY_FOR_FEE_REDUCTION, $applyOrNot);
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
