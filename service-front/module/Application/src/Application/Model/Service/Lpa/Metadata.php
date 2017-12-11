<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

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

    public function setApplyForFeeReduction(Lpa $lpa, $applyOrNot)
    {
        $lpa->metadata[self::APPLY_FOR_FEE_REDUCTION] = $applyOrNot;

        if (!$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata)) {
            throw new \RuntimeException('API client failed to set metadata APPLY_FOR_FEE_REDUCTION for id: '.$lpa->id);
        }
    }

    private function setMetadataByKey(Lpa $lpa, $key)
    {
        if (!array_key_exists($key, $lpa->metadata)) {

            $lpa->metadata[$key] = true;

            if (!$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata)) {
                throw new \RuntimeException('API client failed to set metadata '.$key.' for id: '.$lpa->id.' in '.__METHOD__);
            }

            return true;
        }

        return false;
    }
}
