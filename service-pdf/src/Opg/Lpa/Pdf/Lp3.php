<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\PdftkFactory;

/**
 * Class Lp3
 * @package Opg\Lpa\Pdf
 */
class Lp3 extends AbstractIndividualPdf
{
    /**
     * Constants
     */
    public const MAX_ATTORNEYS_PER_PAGE = 4;

    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var string
     */
    protected string $templateFileName = 'LP3.pdf';

    /**
     * @var NotifiedPerson|null
     */
    private ?NotifiedPerson $personToNotify;

    /**
     * @param Lpa|null $lpa
     * @param NotifiedPerson|null $personToNotify
     * @param PdftkFactory|null $pdftkFactory
     */
    public function __construct(
        ?Lpa $lpa = null,
        ?NotifiedPerson $personToNotify = null,
        ?PdftkFactory $pdftkFactory = null
    ) {
        $this->personToNotify = $personToNotify;

        parent::__construct($lpa, [], $pdftkFactory);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy
     * to the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        $this->populatePageOne($this->personToNotify);
        $this->populatePageTwo($lpa);
        $this->populatePageThree($lpa);
        $this->populatePageFour();

        //  Determine how many additional page three instances were added
        //  If there is an odd number of additional pages then we need to insert a blank page
        $additionalPages = ceil(count($lpa->getDocument()->getPrimaryAttorneys()) / self::MAX_ATTORNEYS_PER_PAGE) - 1;

        if ($additionalPages % 2 == 1) {
            //  Insert a single blank page at the end of the document
            $this->insertBlankPage('end');
        }
    }

    /**
     * @param NotifiedPerson $personToNotify
     *
     * @return void
     */
    private function populatePageOne(NotifiedPerson $personToNotify): void
    {
        $name = $personToNotify->getName();
        $address = $personToNotify->getAddress();

        $this->setData('lpa-document-peopleToNotify-name-title', $name->getTitle())
             ->setData('lpa-document-peopleToNotify-name-first', $name->getFirst())
             ->setData('lpa-document-peopleToNotify-name-last', $name->getLast())
             ->setData('lpa-document-peopleToNotify-address-address1', $address->getAddress1())
             ->setData('lpa-document-peopleToNotify-address-address2', $address->getAddress2())
             ->setData('lpa-document-peopleToNotify-address-address3', $address->getAddress3())
             ->setData('lpa-document-peopleToNotify-address-postcode', $address->getPostcode());

        $this->setFooter('footer-right-page-one', 'lp3');
    }

    /**
     * @param Lpa $lpa
     *
     * @return void
     */
    private function populatePageTwo(Lpa $lpa): void
    {
        //  Set the donor details
        $document = $lpa->getDocument();
        $donor = $document->getDonor();
        $name = $donor->getName();
        $address = $donor->getAddress();

        $this->setData('lpa-document-donor-name-title', $name->getTitle())
            ->setData('lpa-document-donor-name-first', $name->getFirst())
            ->setData('lpa-document-donor-name-last', $name->getLast())
            ->setData('lpa-document-donor-address-address1', $address->getAddress1())
            ->setData('lpa-document-donor-address-address2', $address->getAddress2())
            ->setData('lpa-document-donor-address-address3', $address->getAddress3())
            ->setData('lpa-document-donor-address-postcode', $address->getPostcode());

        //  Set who is applicant
        $this->setData(
            'who-is-applicant',
            ($document->getWhoIsRegistering() == 'donor' ? 'donor' : 'attorney')
        );

        //  Set LPA type
        $this->setData(
            'lpa-type',
            ($document->getType() == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : $document->getType())
        );

        $this->setFooter('footer-right-page-two', 'lp3');
    }

    /**
     * @param Lpa $lpa
     *
     * @return void
     */
    private function populatePageThree(Lpa $lpa, $pageIteration = 0): void
    {
        //  This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new $this(null, null, $this->pdftkFactory) : $this);

        $document = $lpa->getDocument();
        $primaryAttorneys = $document->getPrimaryAttorneys();
        $primaryDecisions = $document->getPrimaryAttorneyDecisions();

        //  Set the details about how attorneys act
        if (count($primaryAttorneys) == 1) {
            $pdf->setData('how-attorneys-act', 'only-one-attorney-appointed');
        } elseif ($primaryDecisions instanceof PrimaryAttorneyDecisions) {
            $pdf->setData('how-attorneys-act', $primaryDecisions->getHow());
        }

        //  Populate the details for primary attorneys on this page
        $primaryAttorneysForPages = array_chunk($primaryAttorneys, self::MAX_ATTORNEYS_PER_PAGE);

        if (array_key_exists($pageIteration, $primaryAttorneysForPages)) {
            $primaryAttorneysForPage = $primaryAttorneysForPages[$pageIteration];

            for ($i = 0; $i < self::MAX_ATTORNEYS_PER_PAGE; $i++) {
                //  If there is a primary attorney for this index then render the details
                if (array_key_exists($i, $primaryAttorneysForPage)) {
                    $primaryAttorney = $primaryAttorneysForPage[$i];
                    $name = $primaryAttorney->getName();
                    $address = $primaryAttorney->getAddress();

                    if ($name instanceof Name || $name instanceof LongName) {
                        $pdf->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-name-title',
                            $name->getTitle()
                        )
                            ->setData(
                                'lpa-document-primaryAttorneys-' . $i . '-name-first',
                                $name->getFirst()
                            )
                            ->setData(
                                'lpa-document-primaryAttorneys-' . $i . '-name-last',
                                $name->getLast()
                            );
                    } elseif (is_string($name)) {
                        $pdf->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', $name);
                    }

                    $pdf->setData(
                        'lpa-document-primaryAttorneys-' . $i . '-address-address1',
                        $address->getAddress1()
                    )
                        ->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-address-address2',
                            $address->getAddress2()
                        )
                        ->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-address-address3',
                            $address->getAddress3()
                        )
                        ->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-address-postcode',
                            $address->getPostcode()
                        );
                } else {
                    //  Add a strike through
                    $pdf->addStrikeThrough('lp3-primaryAttorney-' . $i, 3);
                }
            }

            //  If applicable add the page PDF as a constituent
            if ($pdf !== $this) {
                $this->addConstituentPdfPage($pdf, 3, 3);
            }

            //  If there is another page of primary attorneys trigger again
            $pageIteration++;

            if (array_key_exists($pageIteration, $primaryAttorneysForPages)) {
                $this->populatePageThree($lpa, $pageIteration);
            }
        }

        $pdf->setFooter('footer-right-page-three', 'lp3');
    }

    /**
     * @return void
     */
    private function populatePageFour(): void
    {
        $this->setFooter('footer-right-page-four', 'lp3');
    }
}
