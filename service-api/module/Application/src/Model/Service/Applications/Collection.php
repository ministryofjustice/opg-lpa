<?php

namespace Application\Model\Service\Applications;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Laminas\Paginator\Paginator;

class Collection extends Paginator
{
    public function toArray()
    {
        //  Extract the applications data
        $applications = [];

        $lpas = iterator_to_array($this->getItemsByPage($this->getCurrentPageNumber()));

        //  Get the abbreviated details of the LPA
        foreach ($lpas as $lpa) {
            /** @var $lpa Lpa */
            $lpaData = $lpa->abbreviatedToArray();

            // Append additional useful fields from the LPA which
            // we can use to determine whether its data is reusable
            $lpaData['hasCompletedDonor'] = $lpa->hasDonor();

            // PF LPA => hasWhenLpaStarts
            // HW LPA => hasPrimaryAttorneyDecisions
            $lpaData['hasCompletedWhenLpaConditions'] = $lpa->hasWhenLpaStarts() ||
                $lpa->hasPrimaryAttorneyDecisions();

            $lpaData['hasCompletedPrimaryAttorneys'] = $lpa->hasPrimaryAttorney();
            $lpaData['hasCompletedReplacementAttorneys'] = $lpa->hasDocument() &&
                array_key_exists(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED, $lpa->getMetadata());
            $lpaData['hasCompletedCertificateProvider'] = $lpa->hasCertificateProvider()
                || $lpa->hasCertificateProviderSkipped();
            $lpaData['hasCompletedPeopleToNotify'] = $lpa->hasDocument() &&
                array_key_exists(Lpa::PEOPLE_TO_NOTIFY_CONFIRMED, $lpa->getMetadata());

            $applications[] = $lpaData;
        }

        return [
            'applications' => $applications,
            'total'        => $this->getTotalItemCount(),
        ];
    }
}
