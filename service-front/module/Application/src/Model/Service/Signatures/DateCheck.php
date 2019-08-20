<?php

namespace Application\Model\Service\Signatures;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use DateTime;
use InvalidArgumentException;

class DateCheck
{
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
     * @param   array           $dates
     * @param   boolean         $isDraft
     * @param   IDateService    $dateService
     * @return  array|bool List of errors or true if no errors
     */
    public static function checkDates(array $dates, $isDraft = false, $dateService = null)
    {
        $errors = [];

        $donor = $dates['sign-date-donor'];
        $certificateProvider = $dates['sign-date-certificate-provider'];

        // Donor must be first
        if ($donor > $certificateProvider) {
            $errors['sign-date-certificate-provider'][] = 'The donor must be the first person to sign the LPA. ' . ($isDraft === true ? 'You need to print and re-sign sections 10 and 11' : 'You need to print and re-sign sections 10, 11 and 15');
        }

        $allTimestamps = [
            'sign-date-donor'                => $donor,
            'sign-date-certificate-provider' => $certificateProvider
        ];

        if (isset($dates['sign-date-donor-life-sustaining'])) {
            $donorLifeSustaining = $dates['sign-date-donor-life-sustaining'];
            $allTimestamps['sign-date-donor-life-sustaining'] = $donorLifeSustaining;

            if ($donor < $donorLifeSustaining) {
                $errors['sign-date-donor-life-sustaining'][] = 'The donor must sign Section 5 and any continuation sheets on the same day or before section 9. ' . ($isDraft === true ? 'You need to print and re-sign sections 9, 10 and 11' : 'You need to print and re-sign sections 9, 10, 11 and 15');
            }
        }

        $minAttorneyDate = $dates['sign-date-attorneys'][0];
        $maxAttorneyDate = $dates['sign-date-attorneys'][0];
        for ($i = 0; $i < count($dates['sign-date-attorneys']); $i++) {
            $timestamp = $dates['sign-date-attorneys'][$i];
            $attorneyKey = 'sign-date-attorney-' . $i;
            $allTimestamps[$attorneyKey] = $timestamp;

            if ($timestamp < $minAttorneyDate) {
                $minAttorneyDate = $timestamp;
            }
            if ($timestamp > $maxAttorneyDate) {
                $maxAttorneyDate = $timestamp;
            }

            // Donor must be first
            if ($donor > $timestamp) {
                $errors[$attorneyKey][] = 'The donor must be the first person to sign the LPA. ' . ($isDraft === true ? 'You need to print and re-sign sections 10 and 11' : 'You need to print and re-sign sections 10, 11 and 15');
            }
        }

        // CP must be next
        if ($certificateProvider > $minAttorneyDate) {
            $errors['sign-date-certificate-provider'][] = 'The certificate provider must sign the LPA before the attorneys. ' . ($isDraft === true ? 'You need to print and re-sign section 11' : 'You need to print and re-sign sections 11 and 15');
        }

        if (!$isDraft && isset($dates['sign-date-applicants']) && count($dates['sign-date-applicants']) > 0) {
            for ($i = 0; $i < count($dates['sign-date-applicants']); $i++) {
                $timestamp = $dates['sign-date-applicants'][$i];
                $applicantKey = 'sign-date-applicant-' . $i;
                $allTimestamps[$applicantKey] = $timestamp;

                // Donor must be first
                if ($donor > $timestamp) {
                    $errors[$applicantKey][] = 'The donor must be the first person to sign the LPA. ' . ($isDraft === true ? 'You need to print and re-sign sections 10 and 11' : 'You need to print and re-sign sections 10, 11 and 15');
                }

                // Applicants must sign on or after last attorney
                if ($timestamp < $maxAttorneyDate) {
                    $errors[$applicantKey][] = 'The applicant must sign on the same day or after all section 11s have been signed. ' . ($isDraft === true ? '' : '') . 'You need to print and re-sign section 15';
                }
            }
        }

        $dateService = $dateService ?: new DateService();
        $today = $dateService->getToday()->getTimestamp();
        foreach ($allTimestamps as $timestampKey => $timestamp) {
            if ($timestamp instanceof DateTime) {
                $timestamp = $timestamp->getTimestamp();
            }
            if ($timestamp > $today) {
                if ($timestampKey === 'sign-date-donor') {
                    $errors[$timestampKey][] = 'Check your dates. The donor\'s signature date cannot be in the future';
                } elseif ($timestampKey === 'sign-date-certificate-provider') {
                    $errors[$timestampKey][] = 'Check your dates. The certificate provider\'s signature date cannot be in the future';
                } elseif ($timestampKey === 'sign-date-donor-life-sustaining') {
                    $errors[$timestampKey][] = 'Check your dates. The donor\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-attorney-') === 0) {
                    $errors[$timestampKey][] = 'Check your dates. The attorney\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-applicant-') === 0) {
                    $errors[$timestampKey][] = 'Check your dates. The applicant\'s signature date cannot be in the future';
                } else {
                    throw new InvalidArgumentException("timestampKey {$timestampKey} was not recognised");
                }
            }
        }

        if (count($errors) > 0) {
            return $errors;
        }

        return true;
    }
}
