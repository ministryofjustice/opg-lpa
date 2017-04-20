<?php

namespace Application\Model\Service\Signatures;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class DateCheck implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Check that the donor, certificate provider, and attorneys
     * signed the LPA in the correct order
     *
     * Expects and array [
     *  'donor' => date,
     *  'certificate-provider' => date,
     *    'attorneys' => [
     *      date,
     *      date, // 1 or more attorney dates
     *    ]
     *  ];
     *
     * @param   array   $dates
     * @return  array|boolean   List of errors or true if no errors
     */
    public static function checkDates(array $dates)
    {
        $donor = $dates['donor'];
        $certificateProvider = $dates['certificate-provider'];

        if (isset($dates['donor-life-sustaining'])) {
            $donorLifeSustaining = $dates['donor-life-sustaining'];
        }

        $minAttorneyDate = $dates['attorneys'][0];
        for ($i = 1; $i < count($dates['attorneys']); $i++) {
            $timestamp = $dates['attorneys'][$i];

            if ($timestamp < $minAttorneyDate) {
                $minAttorneyDate = $timestamp;
            }
        }

        if (isset($donorLifeSustaining) && $donor != $donorLifeSustaining) {
            return 'The donor must sign Section 5 and Section 9 on the same date.';
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
}
