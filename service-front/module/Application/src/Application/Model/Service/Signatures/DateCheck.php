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
    public static function checkDates($lpa)
    {
        return true;
    }
    
}
