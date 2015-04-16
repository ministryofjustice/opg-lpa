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

    const REPLACEMENT_ATTORNEYS_CONFIRMED = 'replacement-attorneys-confirmed';
    const PEOPLE_TO_NOTIFY_CONFIRMED = 'people-to-notify-confirmed';
    
    use ServiceLocatorAwareTrait;
    
    public function setReplacementAttorneysConfirmed(Lpa $lpa)
    {
        if(!array_key_exists(self::REPLACEMENT_ATTORNEYS_CONFIRMED, $lpa->metadata)) {
            
            $lpa->metadata[self::REPLACEMENT_ATTORNEYS_CONFIRMED] = true;
            
            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id.' in setLpaHasNoReplacementAttorneys()');
            }
        }
    }
    
    public function setPeopleToNotifyConfirmed(Lpa $lpa)
    {
        if(!array_key_exists(self::PEOPLE_TO_NOTIFY_CONFIRMED, $lpa->metadata)) {
                
            $lpa->metadata[self::PEOPLE_TO_NOTIFY_CONFIRMED] = true;

            if( !$this->getServiceLocator()->get('LpaApplicationService')->setMetaData($lpa->id, $lpa->metadata) ) {
                throw new \RuntimeException('API client failed to set metadata for id: '.$lpa->id.' in setLpaHasNoPeopleToNotify()');
            }
            
            return true;
        }
        
        return false;
    }
    
    //---


} // class
