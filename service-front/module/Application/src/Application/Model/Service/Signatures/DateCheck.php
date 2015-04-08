<?php
namespace Application\Model\Service\Signatures;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class DateCheck implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    /**
     * Check that the donor, certificate provider, and attorneys
     * signed the LPA in the correct order
     * 
     * @param Lpa $lpa
     * @return array List of errors, or empty array if no errors
     */
    public static function checkDates($dates)
    {
        $donor = self::convertUkDateToTimestamp($dates['donor']);
        $certificateProvider = self::convertUkDateToTimestamp($dates['certificate-provider']);
        
        $minAttorneyDate = self::convertUkDateToTimestamp($dates['attorneys'][0]);
        for ($i=1; $i<count($dates['attorneys']); $i++) {
            $timestamp = self::convertUkDateToTimestamp($dates['attorneys'][$i]);
            if ($timestamp < $minAttorneyDate) {
                $minAttorneyDate = $timestamp;
            }
        }
        
        // Donor must be first
        if ($donor > $certificateProvider || $donor > $minAttorneyDate) {
            return false;
        }
        
        // CP must be next
        if ($certificateProvider > $minAttorneyDate) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Convert a date in dd/mm/yyyy format to a timestamp
     * 
     * strtotime documentation:
     * 
     * Dates in the m/d/y or d-m-y formats are disambiguated by looking at the separator
     * between the various components: if the separator is a slash (/), then the 
     * American m/d/y is assumed; whereas if the separator is a dash (-) or a dot (.), 
     * then the European d-m-y format is assumed.
     * 
     * @param string $ukDateString
     * @return number A unix timestamp value
     */
    public function convertUkDateToTimestamp($ukDateString)
    {
        $date = str_replace('/', '-', $ukDateString);
        $YmdDate = date('Y-m-d', strtotime($date));
        
        return strtotime($YmdDate);
    }
    
}
