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
     * signed the LPA in the correct order.
     * If the donor cannot sign, text relating to the donor is
     * replaced with "The person signing on behalf of the donor"
     *
     * Expects $dates array:
     * [
     *  'donor' => date,
     *  'certificate-provider' => date,
     *    'attorneys' => [
     *      date,
     *      date, // 1 or more attorney dates
     *    ]
     *  ];
     *
     * and $donorDetails array:
     *
     * [
     *   'canSign' => bool, // if donor can sign
     *   'isApplicant' => bool // if donor == applicant
     * ]
     *
     * @param   array           $dates
     * @param   boolean         $isDraft
     * @param   array           $donorDetails
     * @param   IDateService    $dateService
     * @return  array|bool List of errors or true if no errors
     */
    public static function checkDates(
        array $dates,
        bool $isDraft = false,
        #[\SensitiveParameter] array $donorDetails = [],
        $dateService = null
    ) {
        $donorCanSign = true;
        if (array_key_exists('canSign', $donorDetails)) {
            $donorCanSign = boolval($donorDetails['canSign']);
        }

        $donorIsApplicant = false;
        if (array_key_exists('isApplicant', $donorDetails)) {
            $donorIsApplicant = boolval($donorDetails['isApplicant']);
        }

        $errors = [];

        $donor = $dates['sign-date-donor'];
        $certificateProvider = $dates['sign-date-certificate-provider'];

        $donorText = 'The person signing on behalf of the donor';
        if ($donorCanSign) {
            $donorText = 'The donor';
        }

        // Donor must sign before certificate provider
        if ($donor > $certificateProvider) {
            $message = "$donorText must be the first person to sign the LPA. ";
            if ($isDraft) {
                $message .= 'You need to print and re-sign sections 10 and 11';
            } else {
                $message .= 'You need to print and re-sign sections 10, 11 and 15';
            }
            $errors['sign-date-certificate-provider'][] = $message;
        }

        $allTimestamps = [
            'sign-date-donor' => $donor,
            'sign-date-certificate-provider' => $certificateProvider
        ];

        // Person signing on behalf of donor must sign donor section before CS3 is signed
        if (isset($dates['sign-date-donor-life-sustaining'])) {
            $donorLifeSustaining = $dates['sign-date-donor-life-sustaining'];
            $allTimestamps['sign-date-donor-life-sustaining'] = $donorLifeSustaining;

            if ($donor < $donorLifeSustaining) {
                $message = "$donorText must sign Section 5 on the same day or before " .
                    'they sign continuation sheet 3. ';

                if ($isDraft) {
                    $message .= 'You need to print and re-sign continuation sheet 3, section 10 and section 11';
                } else {
                    $message .= 'You need to print and re-sign continuation sheet 3 and ' .
                        'sections 10, 11 and 15';
                }
                $errors['sign-date-donor-life-sustaining'][] = $message;
            }
        }

        // Donor must sign before all attorneys
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
                $message = "$donorText must be the first person to sign the LPA. ";
                if ($isDraft) {
                    $message .= 'You need to print and re-sign sections 10 and 11';
                } else {
                    $message .= 'You need to print and re-sign sections 10, 11 and 15';
                }
                $errors[$attorneyKey][] = $message;
            }
        }

        // Certificate provider signature date must be before any attorney signature date
        if ($certificateProvider > $minAttorneyDate) {
            $message = 'The certificate provider must sign the LPA before the attorneys. ';
            if ($isDraft) {
                $message .= 'You need to print and re-sign section 11';
            } else {
                $message .= 'You need to print and re-sign sections 11 and 15';
            }
            $errors['sign-date-certificate-provider'][] = $message;
        }

        // Donor must sign before applicant
        if (!$isDraft && isset($dates['sign-date-applicants']) && count($dates['sign-date-applicants']) > 0) {
            for ($i = 0; $i < count($dates['sign-date-applicants']); $i++) {
                $timestamp = $dates['sign-date-applicants'][$i];
                $applicantKey = 'sign-date-applicant-' . $i;
                $allTimestamps[$applicantKey] = $timestamp;

                // Donor must be first
                if ($donor > $timestamp) {
                    $errors[$applicantKey][] = "$donorText must be the first person to sign the LPA. " .
                        'You need to print and re-sign sections 10, 11 and 15';
                }

                // Applicants must sign on or after last attorney
                if ($timestamp < $maxAttorneyDate) {
                    $applicantSigner = 'applicant';
                    if ($donorIsApplicant && !$donorCanSign) {
                        $applicantSigner = 'person signing on behalf of the applicant';
                    }

                    $errors[$applicantKey][] = 'The ' . $applicantSigner . ' ' .
                        'must sign on the same day or after all section 11s ' .
                        'have been signed. You need to print and re-sign section 15';
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
                if ($timestampKey === 'sign-date-donor' || $timestampKey === 'sign-date-donor-life-sustaining') {
                    $futureDonorDateMessage = 'Check your dates. ';
                    if ($donorCanSign) {
                        $futureDonorDateMessage .= 'The donor\'s signature date';
                    } else {
                        $futureDonorDateMessage .=
                            'The signature date of the person signing on behalf of the donor';
                    }
                    $futureDonorDateMessage .= ' cannot be in the future';

                    $errors[$timestampKey][] = $futureDonorDateMessage;
                } elseif ($timestampKey === 'sign-date-certificate-provider') {
                    $errors[$timestampKey][] =
                        'Check your dates. The certificate provider\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-attorney-') === 0) {
                    $errors[$timestampKey][] =
                        'Check your dates. The attorney\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-applicant-') === 0) {
                    $applicantMessage = 'applicant\'s signature date';
                    if ($donorIsApplicant && !$donorCanSign) {
                        $applicantMessage = 'signature date of the person signing on behalf of the applicant';
                    }

                    $errors[$timestampKey][] =
                        'Check your dates. The ' . $applicantMessage . ' cannot be in the future';
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
