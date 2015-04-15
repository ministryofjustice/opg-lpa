<?php
namespace Application\Model\Service\Lpa;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Used for setting metadata constants etc.
 *
 * Class Metadata
 * @package Application\Model\Service\Lpa
 */
class Metadata implements ServiceLocatorAwareInterface {

    const LPA_HAS_NO_PEOPLE_TO_NOTIFY = 'lpa-has-no-people-to-notify';
    const LPA_HAS_NO_REPLACEMENT_ATTORNEYS = 'lpa-has-no-replacement-attorneys';
    
    use ServiceLocatorAwareTrait;
    
    public function setLpaHasNoReplacementAttorneys(Lpa $lpa)
    {
        if(!array_key_exists(self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS, $lpa->metadata) ||
            ($lpa->metadata[self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS] !== true)) {
                
            $lpa->metadata[self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS] = true;

            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id);
            }
            
            return true;
        }
        
        return false;
    }
    
    public function unsetLpaHasNoReplacementAttorneys(Lpa $lpa)
    {
        if(array_key_exists(self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS, $lpa->metadata) &&
            ($lpa->metadata[self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS] === true)) {
            
            unset($lpa->metadata[self::LPA_HAS_NO_REPLACEMENT_ATTORNEYS]);
            
            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id);
            }
            
            return true;
        }
        
        return false;
    }
    
    public function setLpaHasNoPeopleToNotify(Lpa $lpa)
    {
        if(!array_key_exists(self::LPA_HAS_NO_PEOPLE_TO_NOTIFY, $lpa->metadata) ||
                ($lpa->metadata[self::LPA_HAS_NO_PEOPLE_TO_NOTIFY] !== true)) {
                
            $metaData = $lpa->metadata;
            $metaData[self::LPA_HAS_NO_PEOPLE_TO_NOTIFY] = true;

            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $metaData) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id);
            }
            
            $lpa->metadata[self::LPA_HAS_NO_PEOPLE_TO_NOTIFY] = true;

            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id);
            }
            
            return true;
        }
        
        return false;
    }

    public function unsetLpaHasNoPeopleToNotify(Lpa $lpa)
    {
        if(array_key_exists(self::LPA_HAS_NO_PEOPLE_TO_NOTIFY, $lpa->metadata) &&
            ($lpa->metadata[self::LPA_HAS_NO_PEOPLE_TO_NOTIFY] === true)) {

            unset($lpa->metadata[self::LPA_HAS_NO_PEOPLE_TO_NOTIFY]);

            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id);
            }

            return true;
        }

        return false;
    }
    
    //---


} // class
