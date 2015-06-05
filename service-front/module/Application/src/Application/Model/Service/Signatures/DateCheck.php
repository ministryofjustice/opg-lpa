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
     * Expects and array [
     *  'donor' => 'dd/mm/yyyy',
     *  'certificate-provider' => 'dd/mm/yyyy',
     *    'attorneys' => [
     *      'dd/mm/yyyy',
     *      'dd/mm/yyyy', // 1 or more attorney dates
     *    ]
     *  ];
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
            return 'The donor must be the first person to sign the LPA.';
        }
        
        // CP must be next
        if ($certificateProvider > $minAttorneyDate) {
            return 'The Certificate Provider must sign the LPA before the attorneys.';
        }
        
        return true;
    }
    
    /**
     * Convert a date in dd/mm/yyyy format to a timestamp
     * 
     * strtotime documentation:
     * 
     * "Dates in the m/d/y or d-m-y formats are disambiguated by looking at the separator
     * between the various components: if the separator is a slash (/), then the 
     * American m/d/y is assumed; whereas if the separator is a dash (-) or a dot (.), 
     * then the European d-m-y format is assumed."
     * 
     * We expect a UK date in the format dd/mm/yyyy so we need to convert this to dd-mm-yyyy
     * 
     * @param string $ukDateString
     * @return number A unix timestamp value
     */
    public static function convertUkDateToTimestamp($ukDateString)
    {
        $parts = explode('/', $ukDateString, 3);

        $validFormat = count($parts) == 3 && checkdate($parts[1], $parts[0], $parts[2]);
        
        if (!$validFormat) {
            throw new \Exception('Date not in dd/mm/yyyy format ' . $ukDateString);
        }
        
        $date = str_replace('/', '-', $ukDateString);
        $YmdDate = date('Y-m-d', strtotime($date));
        
        return strtotime($YmdDate);
    }
    
}
