<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet1
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet1 extends AbstractIndividualPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LPC_Continuation_Sheet_1.pdf';

    /**
     * @var array
     */
    private $actorGroups;

    /**
     * @param Lpa $lpa
     * @param array $actorGroups
     */
    public function __construct(Lpa $lpa, array $actorGroups)
    {
        $this->actorGroups = $actorGroups;

        parent::__construct($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Add a leading blank page - this is done for all continuation sheets
        $this->insertBlankPage('start');

        //  Add the donor details
        $this->setData('cs1-donor-full-name', (string) $lpa->document->donor->name);

        $i = 0;

        foreach ($this->actorGroups as $actorType => $actors) {
            foreach ($actors as $actor) {
                $this->setData('cs1-' . $i . '-is', $actorType);

                if ($actor->name instanceof Name) {
                    $this->setData('cs1-' . $i . '-name-title', $actor->name->title)
                         ->setData('cs1-' . $i . '-name-first', $actor->name->first)
                         ->setData('cs1-' . $i . '-name-last', $actor->name->last);
                }

                $this->setData('cs1-' . $i . '-address-address1', $actor->address->address1)
                     ->setData('cs1-' . $i . '-address-address2', $actor->address->address2)
                     ->setData('cs1-' . $i . '-address-address3', $actor->address->address3)
                     ->setData('cs1-' . $i . '-address-postcode', $actor->address->postcode);

                if (property_exists($actor, 'dob')) {
                    $this->setData('cs1-' . $i . '-dob-date-day', $actor->dob->date->format('d'))
                         ->setData('cs1-' . $i . '-dob-date-month', $actor->dob->date->format('m'))
                         ->setData('cs1-' . $i . '-dob-date-year', $actor->dob->date->format('Y'));
                }

                if (property_exists($actor, 'email') && ($actor->email instanceof EmailAddress)) {
                    $this->setData('cs1-' . $i . '-email-address', $actor->email->address, true);
                }

                $i++;
            }
        }

        //  Set footer data
        $this->setFooter('cs1-footer-right', 'cs1');
    }
}
