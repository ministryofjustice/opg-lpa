<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
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
        $this->setData('cs1-donor-full-name', (string) $lpa->getDocument()->getDonor()->name);

        $i = 0;

        foreach ($this->actorGroups as $actorType => $actors) {
            foreach ($actors as $actor) {
                $this->setData('cs1-' . $i . '-is', $actorType);

                $name = $actor->getName();
                $address = $actor->getAddress();

                if ($name instanceof Name || $name instanceof LongName) {
                    $this->setData('cs1-' . $i . '-name-title', $name->getTitle())
                         ->setData('cs1-' . $i . '-name-first', $name->getFirst())
                         ->setData('cs1-' . $i . '-name-last', $name->getLast());
                } elseif (is_string($name)) {
                    $this->setData('cs1-' . $i . '-name-last', (string) $name);
                }

                $this->setData('cs1-' . $i . '-address-address1', $address->getAddress1())
                     ->setData('cs1-' . $i . '-address-address2', $address->getAddress2())
                     ->setData('cs1-' . $i . '-address-address3', $address->getAddress3())
                     ->setData('cs1-' . $i . '-address-postcode', $address->getPostcode());

                if (!$actor instanceof NotifiedPerson) {
                    $email = $actor->getEmail();
                    $dobDate = $actor->getDob()->getDate();

                    if ($dobDate instanceof \DateTime) {
                        $this->setData('cs1-' . $i . '-dob-date-day', $dobDate->format('d'))
                            ->setData('cs1-' . $i . '-dob-date-month', $dobDate->format('m'))
                            ->setData('cs1-' . $i . '-dob-date-year', $dobDate->format('Y'));
                    }

                    if ($email instanceof EmailAddress) {
                        $this->setData('cs1-' . $i . '-email-address', $email->getAddress(), true);
                    }
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
