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
class ContinuationSheet1 extends AbstractContinuationSheet
{
    /**
     * Constants
     */
    const MAX_ACTORS_CONTINUATION_SHEET_1 = 2;

    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var string
     */
    protected string $templateFileName = 'LPC_Continuation_Sheet_1.pdf';

    /**
     * @var array
     */
    private array $actorGroups;

    /**
     * @param Lpa $lpa
     * @param array $actorGroups
     */
    public function __construct(Lpa $lpa, array $actorGroups, ?PdftkFactory $pdftkFactory = null)
    {
        $this->actorGroups = $actorGroups;

        parent::__construct($lpa, [], $pdftkFactory);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        parent::create($lpa);

        //  Add the donor details
        $this->setData('cs1-donor-full-name', (string) $lpa->getDocument()->getDonor()->getName());

        $i = 0;

        foreach ($this->actorGroups as $actorType => $actors) {
            foreach ($actors as $actor) {
                $this->setData('cs1-' . $i . '-is', $actorType);

                if ($actor->name instanceof Name) {
                    $this->setData('cs1-' . $i . '-name-title', $actor->name->title)
                         ->setData('cs1-' . $i . '-name-first', $actor->name->first)
                         ->setData('cs1-' . $i . '-name-last', $actor->name->last);
                }

                $this->setData('cs1-' . $i . '-address-address1', $actor->getAddress()->getAddress1())
                     ->setData('cs1-' . $i . '-address-address2', $actor->getAddress()->getAddress2())
                     ->setData('cs1-' . $i . '-address-address3', $actor->getAddress()->getAddress3())
                     ->setData('cs1-' . $i . '-address-postcode', $actor->getAddress()->getPostcode());

                if (property_exists($actor, 'dob')) {
                    $this->setData('cs1-' . $i . '-dob-date-day', $actor->getDob()->getDate()->format('d'))
                         ->setData('cs1-' . $i . '-dob-date-month', $actor->getDob()->getDate()->format('m'))
                         ->setData('cs1-' . $i . '-dob-date-year', $actor->getDob()->getDate()->format('Y'));
                }

                if (property_exists($actor, 'email') && ($actor->email instanceof EmailAddress)) {
                    $this->setData('cs1-' . $i . '-email-address', $actor->getEmail()->getAddress(), true);
                }

                $i++;

                //  If we have filled the sheet then exit
                if ($i == self::MAX_ACTORS_CONTINUATION_SHEET_1) {
                    break;
                }
            }
        }

        //  If required add a strike through
        if ($i % self::MAX_ACTORS_CONTINUATION_SHEET_1 > 0) {
            $this->addStrikeThrough('cs1');
        }

        //  Set footer data
        $this->setFooter('cs1-footer-right', 'cs1');
    }
}
