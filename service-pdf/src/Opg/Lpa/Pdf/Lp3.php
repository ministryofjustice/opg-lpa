<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class Lp3
 * @package Opg\Lpa\Pdf
 */
class Lp3 extends AbstractIndividualPdf
{
    /**
     * Constants
     */
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;

    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LP3.pdf';

    /**
     * @var NotifiedPerson
     */
    private $personToNotify;

    /**
     * Constructor can be triggered with or without an LPA object
     * If an LPA object is passed then the PDF object will execute the create function to populate the data
     *
     * @param Lpa|null $lpa
     * @param NotifiedPerson|null $personToNotify
     */
    public function __construct(Lpa $lpa = null, NotifiedPerson $personToNotify = null)
    {
        $this->personToNotify = $personToNotify;

        parent::__construct($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        $this->populatePageOne($this->personToNotify);
        $this->populatePageTwo($lpa);
        $this->populatePageThree($lpa);
        $this->populatePageFour();

        //  If there is an even number of attorney pages then append a blank page to the end of the PDF
        $primaryAttorneysForPages = array_chunk($lpa->document->primaryAttorneys, self::MAX_ATTORNEYS_ON_STANDARD_FORM);

        if (count($primaryAttorneysForPages) % 2 == 0) {
            $this->insertBlankPage();
        }
    }

    /**
     * @param NotifiedPerson $personToNotify
     */
    private function populatePageOne(NotifiedPerson $personToNotify)
    {
        $this->setData('lpa-document-peopleToNotify-name-title', $personToNotify->name->title)
             ->setData('lpa-document-peopleToNotify-name-first', $personToNotify->name->first)
             ->setData('lpa-document-peopleToNotify-name-last', $personToNotify->name->last)
             ->setData('lpa-document-peopleToNotify-address-address1', $personToNotify->address->address1)
             ->setData('lpa-document-peopleToNotify-address-address2', $personToNotify->address->address2)
             ->setData('lpa-document-peopleToNotify-address-address3', $personToNotify->address->address3)
             ->setData('lpa-document-peopleToNotify-address-postcode', $personToNotify->address->postcode);

        $this->setData('footer-right-page-one', $this->config['footer']['lp3']);
    }

    /**
     * @param Lpa $lpa
     */
    private function populatePageTwo(Lpa $lpa)
    {
        //  Set the donor details
        $donor = $lpa->document->donor;
        $this->setData('lpa-document-donor-name-title', $donor->name->title)
             ->setData('lpa-document-donor-name-first', $donor->name->first)
             ->setData('lpa-document-donor-name-last', $donor->name->last)
             ->setData('lpa-document-donor-address-address1', $donor->address->address1)
             ->setData('lpa-document-donor-address-address2', $donor->address->address2)
             ->setData('lpa-document-donor-address-address3', $donor->address->address3)
             ->setData('lpa-document-donor-address-postcode', $donor->address->postcode);

        //  Set who is applicant
        $this->setData('who-is-applicant', ($lpa->document->whoIsRegistering == 'donor' ? 'donor' : 'attorney'));

        //  Set LPA type
        $this->setData('lpa-type', ($lpa->document->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : $lpa->document->type));

        $this->setData('footer-right-page-two', $this->config['footer']['lp3']);
    }

    /**
     * @param Lpa $lpa
     */
    private function populatePageThree(Lpa $lpa, $pageIteration = 0)
    {
        //  This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new Lp3() : $this);

        $primaryAttorneys = $lpa->document->primaryAttorneys;

        //  Set the details about how attorneys act
        if (count($primaryAttorneys) == 1) {
            $pdf->setData('how-attorneys-act', 'only-one-attorney-appointed');
        } elseif ($lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $pdf->setData('how-attorneys-act', $lpa->document->primaryAttorneyDecisions->how);
        }

        //  Populate the details for primary attorneys on this page
        $primaryAttorneysForPages = array_chunk($primaryAttorneys, self::MAX_ATTORNEYS_ON_STANDARD_FORM);

        if (array_key_exists($pageIteration, $primaryAttorneysForPages)) {
            $primaryAttorneysForPage = $primaryAttorneysForPages[$pageIteration];

            for ($i = 0; $i < self::MAX_ATTORNEYS_ON_STANDARD_FORM; $i++) {
                //  If there is a primary attorney for this index then render the details
                if (array_key_exists($i, $primaryAttorneysForPage)) {
                    $primaryAttorney = $primaryAttorneysForPage[$i];

                    if ($primaryAttorney->name instanceof Name || $primaryAttorney->name instanceof LongName) {
                        $pdf->setData('lpa-document-primaryAttorneys-' . $i . '-name-title', $primaryAttorney->name->title)
                            ->setData('lpa-document-primaryAttorneys-' . $i . '-name-first', $primaryAttorney->name->first)
                            ->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', $primaryAttorney->name->last);
                    } elseif (is_string($primaryAttorney->name)) {
                        $pdf->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', $primaryAttorney->name);
                    }

                    $pdf->setData('lpa-document-primaryAttorneys-' . $i . '-address-address1', $primaryAttorney->address->address1)
                        ->setData('lpa-document-primaryAttorneys-' . $i . '-address-address2', $primaryAttorney->address->address2)
                        ->setData('lpa-document-primaryAttorneys-' . $i . '-address-address3', $primaryAttorney->address->address3)
                        ->setData('lpa-document-primaryAttorneys-' . $i . '-address-postcode', $primaryAttorney->address->postcode);
                } else {
                    //  Add a strikethrough
                    $pdf->addStrikeThrough('lp3-primaryAttorney-' . $i, 3);
                }
            }

            //  If applicable add the page PDF as a constituent
            if ($pdf !== $this) {
                $insertPosition = 3 + $pageIteration;  //  The first page will be inserted as page 4
                $this->addConstituentPdfPage($pdf, 3, $insertPosition);
            }

            //  If there is another page of primary attorneys trigger again
            $nextPage = $pageIteration + 1;

            if (array_key_exists($nextPage, $primaryAttorneysForPages)) {
                $this->populatePageThree($lpa, $nextPage);
            }
        }

        $this->setData('footer-right-page-three', $this->config['footer']['lp3']);
    }

    private function populatePageFour()
    {
        $this->setData('footer-right-page-four', $this->config['footer']['lp3']);
    }
}
