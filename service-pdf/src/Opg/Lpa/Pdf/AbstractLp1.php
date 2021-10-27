<?php

namespace Opg\Lpa\Pdf;

use DateTime;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\Pdf\Aggregator\ContinuationSheet1 as ContinuationSheet1Aggregator;
use Opg\Lpa\Pdf\Aggregator\ContinuationSheet2 as ContinuationSheet2Aggregator;
use Opg\Lpa\Pdf\PdftkFactory;
use Opg\Lpa\Pdf\Traits\LongContentTrait;
use Exception;
use mikehaertl\pdftk\Pdf as Pdftk;
use Laminas\Barcode\Barcode;

/**
 * Class AbstractLp1
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractLp1 extends AbstractIndividualPdf
{
    use LongContentTrait;

    /**
     * Constants
     */
    public const MAX_ATTORNEYS_PER_PAGE_SECTION_11 = 4;
    public const MAX_APPLICANTS_SECTION_12 = 4;
    public const MAX_SIGNATURES_SECTION_15 = 4;

    /**
     * PDF file name for the coversheet
     *
     * @var string
     */
    protected string $coversheetFileName;

    /**
     * PDF file name for the draft coversheet
     *
     * @var string
     */
    protected string $coversheetFileNameDraft;

    /**
     * Flag to indicate if the LPA should be considered completed or not - assume not until proven otherwise
     *
     * @var bool
     */
    private bool $lpaIsComplete = false;

    /**
     * @param Lpa|null $lpa
     * @param array $options
     * @param ?PdftkFactory $pdftkFactory
     * @throws Exception
     */
    public function __construct(Lpa $lpa = null, array $options = [], ?PdftkFactory $pdftkFactory = null)
    {
        // Check that the coversheet variables have been set
        if (is_null($this->coversheetFileName)) {
            throw new Exception('PDF coversheet file name must be defined to create an LP1');
        }

        // If an LPA has been provided - check that it has been completed
        if ($lpa instanceof Lpa) {
            $this->lpaIsComplete = $lpa->isStateCompleted() &&
                $lpa->getCompletedAt() instanceof DateTime;
        }

        parent::__construct($lpa, $options, $pdftkFactory);
    }

    /**
     * Create the PDF in preparation for it to be generated - this
     * function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        // Add an appropriate coversheet to the start of the document
        $this->insertStaticPDF($this->lpaIsComplete ?
            $this->coversheetFileName : $this->coversheetFileNameDraft, 1, 2, 'start');

        $document = $lpa->getDocument();
        $donor = $document->getDonor();

        $this->populatePageOne($donor);
        $this->populatePageTwoThreeFour($document);
        $this->populatePageFive($document);
        $this->populatePageSix($document->getPrimaryAttorneyDecisions());
        $this->populatePageSeven($document->getPeopleToNotify());
        $this->populatePageEight($document);
        // No content on page 9
        $this->populatePageTen($donor);
        $this->populatePageEleven($document->getCertificateProvider());
        $this->populatePageTwelveThirteenFourteenFifteen($lpa);
        // No content on page 16
        $this->populatePageSeventeen($document);
        $this->populatePageEighteen($document->getCorrespondent());
        $this->populatePageNineteen($lpa->getPayment(), $lpa->getRepeatCaseNumber());
        $this->populatePageTwenty($document->getWhoIsRegistering());

        // Add any continuation sheets - this must take place AFTER the core content
        // is set above to ensure that pages are inserted in the correct order
        $this->addContinuationSheets($lpa);

        $this->setFooterContent($lpa);
    }

    /**
     * @param Donor $donor
     *
     * @return void
     */
    private function populatePageOne(Donor $donor): void
    {
        $name = $donor->getName();
        $dobDate = $donor->getDob()->getDate();
        $address = $donor->getAddress();

        $this->setData('lpa-document-donor-name-title', $name->getTitle())
            ->setData('lpa-document-donor-name-first', $name->getFirst())
            ->setData('lpa-document-donor-name-last', $name->getLast())
            ->setData('lpa-document-donor-otherNames', $donor->getOtherNames())
            ->setData('lpa-document-donor-dob-date-day', $dobDate->format('d'))
            ->setData('lpa-document-donor-dob-date-month', $dobDate->format('m'))
            ->setData('lpa-document-donor-dob-date-year', $dobDate->format('Y'))
            ->setData('lpa-document-donor-address-address1', $address->getAddress1())
            ->setData('lpa-document-donor-address-address2', $address->getAddress2())
            ->setData('lpa-document-donor-address-address3', $address->getAddress3())
            ->setData('lpa-document-donor-address-postcode', $address->getPostcode());

        if ($donor->getEmail() instanceof EmailAddress) {
            $this->setData('lpa-document-donor-email-address', $donor->getEmail()->getAddress());
        }
    }

    /**
     * @param Document $lpaDocument
     *
     * @return void
     */
    private function populatePageTwoThreeFour(Document $lpaDocument): void
    {
        $primaryAttorneys = $this->getOrderedAttorneys($lpaDocument->getPrimaryAttorneys());

        for ($i = 0; $i < self::MAX_ATTORNEYS_SECTION_2; $i++) {
            // If there is a primary attorney for this index then render the details
            if (array_key_exists($i, $primaryAttorneys)) {
                $primaryAttorney = $primaryAttorneys[$i];
                $address = $primaryAttorney->getAddress();

                if ($primaryAttorney instanceof TrustCorporation) {
                    $this->setCheckBox('attorney-' . $i . '-is-trust-corporation')
                         ->setData(
                             'lpa-document-primaryAttorneys-' . $i . '-name-last',
                             (string) $primaryAttorney->getName()
                         );
                } else {
                    $name = $primaryAttorney->getName();
                    $dobDate = $primaryAttorney->getDob()->getDate();

                    $this->setData('lpa-document-primaryAttorneys-' . $i . '-name-title', $name->getTitle())
                        ->setData('lpa-document-primaryAttorneys-' . $i . '-name-first', $name->getFirst())
                        ->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', $name->getLast())
                        ->setData('lpa-document-primaryAttorneys-' . $i .
                            '-dob-date-day', $dobDate->format('d'))
                        ->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-dob-date-month',
                            $dobDate->format('m')
                        )
                        ->setData(
                            'lpa-document-primaryAttorneys-' . $i . '-dob-date-year',
                            $dobDate->format('Y')
                        );
                }

                $this->setData(
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

                if ($primaryAttorney->getEmail() instanceof EmailAddress) {
                    $this->setData(
                        'lpa-document-primaryAttorneys-' . $i . '-email-address',
                        $primaryAttorney->getEmail()->getAddress(),
                        true
                    );
                }
            } else {
                // Add a strike through on the appropriate page
                $strikeThroughArea = 'primaryAttorney-' . $i;

                // Determine what page number this is
                $strikeThroughPage = 2 + floor($i / 2);

                if ($strikeThroughPage == 2) {
                    // Add the required strike through area prefix
                    $strikeThroughArea .= '-' . $this->getAreaReferenceSuffix();
                }

                $this->addStrikeThrough($strikeThroughArea, $strikeThroughPage);
            }
        }

        if (count($primaryAttorneys) > self::MAX_ATTORNEYS_SECTION_2) {
            $this->setCheckBox('has-more-than-4-attorneys');
        }

        // Set the attorney decisions value
        if (count($primaryAttorneys) == 1) {
            $this->setData('how-attorneys-act', 'only-one-attorney-appointed');
        } elseif ($lpaDocument->getPrimaryAttorneyDecisions() instanceof PrimaryAttorneyDecisions) {
            $this->setData('how-attorneys-act', $lpaDocument->getPrimaryAttorneyDecisions()->getHow());
        }
    }

    /**
     * @param Document $lpaDocument
     *
     * @return void
     */
    private function populatePageFive(Document $lpaDocument): void
    {
        $replacementAttorneys = $this->getOrderedAttorneys($lpaDocument->getReplacementAttorneys());

        for ($i = 0; $i < self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4; $i++) {
            // If there is a replacement attorney for this index then render the details
            if (array_key_exists($i, $replacementAttorneys)) {
                $replacementAttorney = $replacementAttorneys[$i];
                $address = $replacementAttorney->getAddress();

                if ($replacementAttorney instanceof TrustCorporation) {
                    $this->setCheckBox('replacement-attorney-' . $i . '-is-trust-corporation')
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-name-last',
                            (string) $replacementAttorney->getName()
                        );
                } else {
                    $name = $replacementAttorney->getName();
                    $dobDate = $replacementAttorney->getDob()->getDate();

                    $this->setData(
                        'lpa-document-replacementAttorneys-' . $i . '-name-title',
                        $name->getTitle()
                    )
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-name-first',
                            $name->getFirst()
                        )
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-name-last',
                            $name->getLast()
                        )
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-dob-date-day',
                            $dobDate->format('d')
                        )
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-dob-date-month',
                            $dobDate->format('m')
                        )
                        ->setData(
                            'lpa-document-replacementAttorneys-' . $i . '-dob-date-year',
                            $dobDate->format('Y')
                        );
                }

                $this->setData(
                    'lpa-document-replacementAttorneys-' . $i . '-address-address1',
                    $address->getAddress1()
                )
                    ->setData(
                        'lpa-document-replacementAttorneys-' . $i . '-address-address2',
                        $address->getAddress2()
                    )
                    ->setData(
                        'lpa-document-replacementAttorneys-' . $i . '-address-address3',
                        $address->getAddress3()
                    )
                    ->setData(
                        'lpa-document-replacementAttorneys-' . $i . '-address-postcode',
                        $address->getPostcode()
                    );

                if ($replacementAttorney->getEmail() instanceof EmailAddress) {
                    $this->setData(
                        'lpa-document-replacementAttorneys-' . $i . '-email-address',
                        $replacementAttorney->getEmail()->getAddress(),
                        true
                    );
                }
            } else {
                // Add a strike through on the appropriate page
                $strikeThroughArea = 'replacementAttorney-' . $i . '-' . $this->getAreaReferenceSuffix();
                $strikeThroughPage = 5 + floor($i / 2);

                $this->addStrikeThrough($strikeThroughArea, $strikeThroughPage);
            }
        }

        if (count($replacementAttorneys) > self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4) {
            $this->setCheckBox('has-more-than-2-replacement-attorneys');
        }

        // Determine whether to check the when/how my attorneys can act checkbox
        $replacementAttorneysContent = $this->getHowWhenReplacementAttorneysCanActContent($lpaDocument);

        if (!empty($replacementAttorneysContent)) {
            $this->setCheckBox('change-how-replacement-attorneys-step-in');
        }
    }

    /**
     * Populate page 6 - difference for each LPA type
     *
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     */
    abstract protected function populatePageSix(PrimaryAttorneyDecisions $primaryAttorneyDecisions = null);

    /**
     * @param array $peopleToNotify
     *
     * @return void
     */
    private function populatePageSeven(array $peopleToNotify): void
    {
        // Ensure array has correct indexes
        $peopleToNotify = array_values($peopleToNotify);

        for ($i = 0; $i < self::MAX_PEOPLE_TO_NOTIFY_SECTION_6; $i++) {
            // If there is a person to notify for this index then render the details
            if (array_key_exists($i, $peopleToNotify)) {
                $personToNotify = $peopleToNotify[$i];

                $this->setData('lpa-document-peopleToNotify-' . $i . '-name-title', $personToNotify->name->title)
                    ->setData('lpa-document-peopleToNotify-' . $i . '-name-first', $personToNotify->name->first)
                    ->setData('lpa-document-peopleToNotify-' . $i . '-name-last', $personToNotify->name->last)
                    ->setData(
                        'lpa-document-peopleToNotify-' . $i . '-address-address1',
                        $personToNotify->address->address1
                    )
                    ->setData(
                        'lpa-document-peopleToNotify-' . $i . '-address-address2',
                        $personToNotify->address->address2
                    )
                    ->setData(
                        'lpa-document-peopleToNotify-' . $i . '-address-address3',
                        $personToNotify->address->address3
                    )
                    ->setData(
                        'lpa-document-peopleToNotify-' . $i . '-address-postcode',
                        $personToNotify->address->postcode
                    );
            } else {
                // Add a strike through on the appropriate page
                $this->addStrikeThrough('people-to-notify-' . $i, 7);
            }
        }

        if (count($peopleToNotify) > self::MAX_PEOPLE_TO_NOTIFY_SECTION_6) {
            // TODO - Historic bug - the check box on the H&W PDF is named incorrecly
            $this->setCheckBox('has-more-than-4-notified-people')   //Property and Finance
                ->setCheckBox('has-more-than-5-notified-people');  //Health and Welfare
        }
    }

    /**
     * @param Document $lpaDocument
     *
     * @return void
     */
    private function populatePageEight(Document $lpaDocument): void
    {
        $details = [
            'preference'  => [
                'hasMoreKey' => 'has-more-preferences',
                'detailString' => $lpaDocument->getPreference(),
            ],
            'instruction' => [
                'hasMoreKey' => 'has-more-instructions',
                'detailString' => $lpaDocument->getInstruction(),
            ],
        ];

        foreach ($details as $dataKey => $data) {
            $detailString = $data['detailString'];
            if (empty($detailString)) {
                $this->addStrikeThrough($dataKey, 8);
            } else {
                $this->setData(
                    'lpa-document-' . $dataKey,
                    $this->getInstructionsAndPreferencesContent($detailString)
                );

                if ($this->fillsInstructionsPreferencesBox($detailString)) {
                    $this->setCheckBox($data['hasMoreKey']);
                }
            }
        }
    }

    /**
     * @param Donor $donor
     *
     * @return void
     */
    private function populatePageTen(Donor $donor): void
    {
        if ($donor->isCanSign() === false) {
            $this->setData('see_continuation_sheet_3', 'see continuation sheet 3');
        }
    }

    /**
     * @param CertificateProvider $certificateProvider
     *
     * @return void
     */
    private function populatePageEleven(CertificateProvider $certificateProvider): void
    {
        $name = $certificateProvider->getName();
        $address = $certificateProvider->getAddress();

        $this->setData('lpa-document-certificateProvider-name-title', $name->getTitle())
            ->setData('lpa-document-certificateProvider-name-first', $name->getFirst())
            ->setData('lpa-document-certificateProvider-name-last', $name->getLast())
            ->setData('lpa-document-certificateProvider-address-address1', $address->getAddress1())
            ->setData('lpa-document-certificateProvider-address-address2', $address->getAddress2())
            ->setData('lpa-document-certificateProvider-address-address3', $address->getAddress3())
            ->setData('lpa-document-certificateProvider-address-postcode', $address->getPostcode());
    }

    /**
     * @param Lpa $lpa
     * @param int $pageIteration
     *
     * @return void
     */
    private function populatePageTwelveThirteenFourteenFifteen(Lpa $lpa, int $pageIteration = 0): void
    {
        // This page is repeatable so determine which PDF object to use
        // For the first MAX_ATTORNEYS_PER_PAGE_SECTION_11 number of pages we should populate the main document
        $pdf = ($pageIteration >= self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 ?
            new $this(null, [], $this->pdftkFactory) :
            $this
        );

        // Immediately get an array of all attorneys excluding trusts so we can work with it below
        $attorneys = array_merge(
            $lpa->getDocument()->getPrimaryAttorneys(),
            $lpa->getDocument()->getReplacementAttorneys()
        );

        foreach ($attorneys as $i => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                unset($attorneys[$i]);
                $attorneys = array_values($attorneys);
                break;
            }
        }

        // Try to get the attorney for this page
        $attorney = null;

        if (array_key_exists($pageIteration, $attorneys)) {
            $attorney = $attorneys[$pageIteration];
        }

        // Populate the page details for a human attorney
        if ($attorney instanceof Human) {
            // Determine which index key ref to use - for the first MAX_ATTORNEYS_PER_PAGE_SECTION_11
            // number of pages iterate up
            // But stop at MAX_ATTORNEYS_PER_PAGE_SECTION_11 - 1
            $dataKeyIndex = min($pageIteration, self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 - 1);

            $name = $attorney->getName();

            $pdf->setData('signature-attorney-' . $dataKeyIndex . '-name-title', $name->getTitle())
                ->setData('signature-attorney-' . $dataKeyIndex . '-name-first', $name->getFirst())
                ->setData('signature-attorney-' . $dataKeyIndex . '-name-last', $name->getLast());
        } else {
            // Add a strike through on the appropriate page
            $pageNumber = 12 + $pageIteration;
            $strikeThroughArea = 'attorney-signature-' . $this->getAreaReferenceSuffix();
            $pdf->addStrikeThrough($strikeThroughArea, $pageNumber);
        }

        // Set the fotter content too
        $pdf->setFooterContent($lpa);

        // If applicable add the page PDF as a constituent
        if ($pdf !== $this) {
            // We will be using page 15 for this purpose to make sure the PDF page numbering is sequential
            $this->addConstituentPdfPage($pdf, 15, 15);
        }

        // If there is another attorney available, or we are still populating the first
        // MAX_ATTORNEYS_PER_PAGE_SECTION_11 number of pages, trigger again
        $pageIteration++;

        if ($pageIteration < self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 || array_key_exists($pageIteration, $attorneys)) {
            $this->populatePageTwelveThirteenFourteenFifteen($lpa, $pageIteration);
        }
    }

    /**
     * @param Document $lpaDocument
     * @param int $pageIteration
     *
     * @return void
     */
    private function populatePageSeventeen(Document $lpaDocument, int $pageIteration = 0): void
    {
        // This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new $this(null, [], $this->pdftkFactory) : $this);

        $applicantType = 'donor';
        $strikeThroughIndex = 0;

        if (is_array($lpaDocument->getWhoIsRegistering())) {
            $applicantType = 'attorney';

            $attorneysForPages = array_chunk($lpaDocument->getWhoIsRegistering(), self::MAX_APPLICANTS_SECTION_12);

            if (array_key_exists($pageIteration, $attorneysForPages)) {
                $attorneysForPage = $attorneysForPages[$pageIteration];

                // Insert the specific attorney details
                foreach ($attorneysForPage as $i => $attorneyId) {
                    $attorney = $lpaDocument->getPrimaryAttorneyById($attorneyId);

                    if ($attorney instanceof TrustCorporation) {
                        $pdf->setData('applicant-' . $i . '-name-last', $attorney->getName());
                    } elseif ($attorney instanceof Human) {
                        // we know $attorney must be a Human at this point, but we have to make sure otherwise
                        // there's no guarantee the attorney instance has a getName() method
                        $name = $attorney->getName();
                        $dobDate = $attorney->getDob()->getDate();

                        $pdf->setData('applicant-' . $i . '-name-title', $name->getTitle())
                            ->setData('applicant-' . $i . '-name-first', $name->getFirst())
                            ->setData('applicant-' . $i . '-name-last', $name->getLast())
                            ->setData('applicant-' . $i . '-dob-date-day', $dobDate->format('d'))
                            ->setData('applicant-' . $i . '-dob-date-month', $dobDate->format('m'))
                            ->setData('applicant-' . $i . '-dob-date-year', $dobDate->format('Y'));
                    }

                    $strikeThroughIndex++;
                }

                // If applicable add the page PDF as a constituent
                if ($pdf !== $this) {
                    $this->addConstituentPdfPage($pdf, 17, 17);
                }

                // If there is another page available trigger again
                $pageIteration++;

                if (array_key_exists($pageIteration, $attorneysForPages)) {
                    $this->populatePageSeventeen($lpaDocument, $pageIteration);
                }
            }
        }

        $pdf->setData('who-is-applicant', $applicantType);

        // Draw the strike throughs
        while ($strikeThroughIndex < self::MAX_APPLICANTS_SECTION_12) {
            $pdf->addStrikeThrough(
                'applicant-' . $strikeThroughIndex . '-' . $this->getAreaReferenceSuffix(),
                17
            );
            $strikeThroughIndex++;
        }
    }

    /**
     * @param Correspondence $correspondent
     *
     * @return void
     */
    private function populatePageEighteen(Correspondence $correspondent = null): void
    {
        if (!is_null($correspondent)) {
            $who = $correspondent->getWho();

            // Set the correspondent type - if this is the certificate provider then set it to other
            $correspondentType = ($who == Correspondence::WHO_CERTIFICATE_PROVIDER ?
                Correspondence::WHO_OTHER : $who);
            $this->setData('who-is-correspondent', $correspondentType);

            // Display the name details
            if ($who == Correspondence::WHO_DONOR) {
                // No need to display the name for the donor
                $this->addStrikeThrough('correspondent-empty-name-address', 18);
            } else {
                $name = $correspondent->getName();
                if ($name instanceof LongName) {
                    $this->setData('lpa-document-correspondent-name-title', $name->getTitle())
                        ->setData('lpa-document-correspondent-name-first', $name->getFirst())
                        ->setData('lpa-document-correspondent-name-last', $name->getLast());
                }

                $this->setData(
                    'lpa-document-correspondent-company',
                    (isset($correspondent->company) ? $correspondent->getCompany() : '')
                );

                // If the correspondent is an attorney then strike through the address field
                if ($who == Correspondence::WHO_ATTORNEY) {
                    $this->addStrikeThrough('correspondent-empty-address', 18);
                } else {
                    $address = $correspondent->getAddress();

                    // The correspondent is "other" so display the full address
                    $this->setData('lpa-document-correspondent-address-address1', $address->getAddress1())
                        ->setData('lpa-document-correspondent-address-address2', $address->getAddress2())
                        ->setData('lpa-document-correspondent-address-address3', $address->getAddress3())
                        ->setData('lpa-document-correspondent-address-postcode', $address->getPostcode());
                }
            }

            // Set the contact preferences
            if ($correspondent->isContactByPost() === true) {
                $this->setCheckBox('correspondent-contact-by-post');
            }

            if ($correspondent->getPhone() instanceof PhoneNumber) {
                $this->setCheckBox('correspondent-contact-by-phone')
                    ->setData(
                        'lpa-document-correspondent-phone-number',
                        str_replace(" ", "", $correspondent->getPhone()->getNumber())
                    );
            }

            if ($correspondent->getEmail() instanceof EmailAddress) {
                $this->setCheckBox('correspondent-contact-by-email')
                    ->setData('lpa-document-correspondent-email-address', $correspondent->getEmail()->getAddress());
            }

            if ($correspondent->isContactInWelsh() === true) {
                $this->setCheckBox('correspondent-contact-in-welsh');
            }
        }
    }

    /**
     * @param Payment|null $payment
     * @param int|null $repeatCaseNumber
     */
    private function populatePageNineteen(Payment $payment = null, ?int $repeatCaseNumber = null): void
    {
        if ($payment instanceof Payment) {
            $method = $payment->getMethod();

            if ($method) {
                $this->setData('pay-by', $method);
            }

            if ($method == Payment::PAYMENT_TYPE_CARD) {
                $this->setData('lpa-payment-phone-number', 'NOT REQUIRED.');
            }

            if (
                ($payment->isReducedFeeReceivesBenefits() && $payment->isReducedFeeAwardedDamages())
                || $payment->isReducedFeeLowIncome()
                || $payment->isReducedFeeUniversalCredit()
            ) {
                $this->setCheckBox('apply-for-fee-reduction');
            }

            // Set any online payment details
            if (!is_null($payment->getReference())) {
                $paymentDate = $payment->getDate();

                $this->setData('lpa-payment-reference', $payment->getReference())
                    ->setData('lpa-payment-amount', '£' . sprintf('%.2f', $payment->getAmount()))
                    ->setData('lpa-payment-date-day', $paymentDate->format('d'))
                    ->setData('lpa-payment-date-month', $paymentDate->format('m'))
                    ->setData('lpa-payment-date-year', $paymentDate->format('Y'));
            }
        }

        if (!is_null($this->formattedLpaRef) && $this->lpaIsComplete) {
            $this->setData('lpa-a-reference-number', $this->formattedLpaRef);
        }

        // Set repeat application details
        if (!is_null($repeatCaseNumber)) {
            $this->setCheckBox('is-repeat-application')
                 ->setData('repeat-application-case-number', $repeatCaseNumber);
        }
    }

    /**
     * @param array|string $whoIsRegistering
     * @param int $pageIteration
     *
     * @return void
     */
    private function populatePageTwenty($whoIsRegistering, int $pageIteration = 0): void
    {
        // This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new $this(null, [], $this->pdftkFactory) : $this);

        // There must always be at least one signature
        $blankIndex = 1;

        if (is_array($whoIsRegistering)) {
            $signaturesForPages = array_chunk($whoIsRegistering, self::MAX_SIGNATURES_SECTION_15);

            if (array_key_exists($pageIteration, $signaturesForPages)) {
                $signaturesForPage = $signaturesForPages[$pageIteration];

                $blankIndex = count($signaturesForPage);

                // If applicable add the page PDF as a constituent
                if ($pdf !== $this) {
                    // Insert the page at the end of the document
                    $this->addConstituentPdfPage($pdf, 20, 'end');
                }

                // If there is another page available trigger again
                $pageIteration++;

                if (array_key_exists($pageIteration, $signaturesForPages)) {
                    $this->populatePageTwenty($whoIsRegistering, $pageIteration);
                }
            }
        }

        // Draw the blanks
        while ($blankIndex < self::MAX_SIGNATURES_SECTION_15) {
            $pdf->addBlank('applicant-signature-' . $blankIndex . '-' . $this->getAreaReferenceSuffix(), 20);
            $blankIndex++;
        }
    }

    /**
     * @return string
     */
    abstract protected function getAreaReferenceSuffix();

    /**
     * @param Lpa $lpa
     *
     * @return void
     */
    private function setFooterContent(Lpa $lpa): void
    {
        $footerContentRef = ($lpa->getDocument()->getType() == Document::LPA_TYPE_PF ? 'lp1f' : 'lp1h');

        $this->setFooter('footer-instrument-right', $footerContentRef);
        $this->setFooter('footer-registration-right', $footerContentRef . '-draft');
    }

    /**
     * @param Lpa $lpa
     *
     * @return void
     */
    private function addContinuationSheets(Lpa $lpa): void
    {
        $continuationSheetsAdded = false;
        $document = $lpa->getDocument();

        // Add continuation sheet 1 instances if required
        $primaryAttorneys = $this->getOrderedAttorneys($document->getPrimaryAttorneys());
        $primaryAttorneys = array_splice($primaryAttorneys, self::MAX_ATTORNEYS_SECTION_2);
        $replacementAttorneys = $this->getOrderedAttorneys($document->getReplacementAttorneys());
        $replacementAttorneys = array_splice($replacementAttorneys, self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4);
        $peopleToNotify = array_splice($document->getPeopleToNotify(), self::MAX_PEOPLE_TO_NOTIFY_SECTION_6);

        if (!empty($primaryAttorneys) || !empty($replacementAttorneys) || !empty($peopleToNotify)) {
            $continuationSheet1 = new ContinuationSheet1Aggregator(
                $lpa,
                $primaryAttorneys,
                $replacementAttorneys,
                $peopleToNotify,
                $this->pdftkFactory
            );
            $this->addConstituentPdf($continuationSheet1, 1, $continuationSheet1->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 2 (primary attorney decisions) instances if required
        if ($document->getPrimaryAttorneyDecisions()->getHow() == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $continuationSheet2 = new ContinuationSheet2Aggregator(
                $lpa,
                ContinuationSheet2::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS,
                $this->pdftkFactory
            );
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 2 (how replacement attorneys can act) instances if required
        $replacementAttorneysContent = $this->getHowWhenReplacementAttorneysCanActContent($document);

        if (!empty($replacementAttorneysContent)) {
            $continuationSheet2 = new ContinuationSheet2Aggregator(
                $lpa,
                ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN,
                $this->pdftkFactory
            );
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 2 (preferences) instances if required
        if ($this->fillsInstructionsPreferencesBox($document->getPreference())) {
            $continuationSheet2 = new ContinuationSheet2Aggregator(
                $lpa,
                ContinuationSheet2::CS2_TYPE_PREFERENCES,
                $this->pdftkFactory
            );
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 2 (instructions) instances if required
        if ($this->fillsInstructionsPreferencesBox($document->getInstruction())) {
            $continuationSheet2 = new ContinuationSheet2Aggregator(
                $lpa,
                ContinuationSheet2::CS2_TYPE_INSTRUCTIONS,
                $this->pdftkFactory
            );
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 3 instances if required
        if ($document->getDonor()->isCanSign() === false) {
            $continuationSheet3 = new ContinuationSheet3($lpa, [], $this->pdftkFactory);
            $this->addConstituentPdf($continuationSheet3, 1, 2, 15);

            $continuationSheetsAdded = true;
        }

        // Add continuation sheet 4 instances if required
        $attorneys = array_merge($document->getPrimaryAttorneys(), $document->getReplacementAttorneys());

        if ($this->getTrustAttorney($attorneys) instanceof TrustCorporation) {
            $continuationSheet4 = new ContinuationSheet4($lpa, [], $this->pdftkFactory);
            $this->addConstituentPdf($continuationSheet4, 1, 2, 15);

            $continuationSheetsAdded = true;
        }

        // If any continuation sheets have been added append another blank page
        if ($continuationSheetsAdded) {
            $this->insertBlankPage(15);
        }
    }

    /**
     * Return a sorted (trust first) array of attorneys
     *
     * @param array $attorneys
     *
     * @return list<TrustCorporation|mixed>
     */
    private function getOrderedAttorneys(array $attorneys): array
    {
        foreach ($attorneys as $i => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                // Recreate the attorney array with the trust at the start
                unset($attorneys[$i]);
                $attorneys = array_values($attorneys);
                array_unshift($attorneys, $attorney);
                break;
            }
        }

        // Ensure array indexes are correct
        return array_values($attorneys);
    }

    /**
     * @param array $attorneys
     *
     * @return mixed
     */
    private function getTrustAttorney(array $attorneys)
    {
        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                return $attorney;
            }
        }

        return null;
    }

    /**
     * Generate the PDF - this will save a copy to the file system
     *
     * @param bool $protect
     * @throws Exception
     *
     * @return string
     */
    public function generate(bool $protect = false): string
    {
        // Generate the LP1 PDF - don't protect the PDF at this point
        // The password will only be set below if this in the main PDF being generated
        $pdfFile = parent::generate(false);

        // Only perform the stamping if this is the main PDF being generated rather than a constituent PDF
        if (!is_null($this->formattedLpaRef)) {
            // If the LPA is completed then stamp it with a barcode
            if ($this->lpaIsComplete) {
                // Generate the barcode
                $renderer = Barcode::factory(
                    'code39',
                    'pdf',
                    [
                        'text' => str_replace(' ', '', $this->formattedLpaRef),
                        'drawText' => false,
                        'factor' => 2,
                        'barHeight' => 25,
                    ],
                    [
                        'topOffset' => 789,
                        'leftOffset' => 40,
                    ]
                );

                // Create a blank PDF with the barcode only
                $barcodeOnlyPdf = $renderer->draw();
                $barcodePdfFile = $this->getIntermediatePdfFilePath('barcode.pdf');
                $barcodeOnlyPdf->save($barcodePdfFile);

                // Stamp the required page with the new barcode using the unshifted page number
                $this->stampPageWith($barcodePdfFile, 19, false);

                // Cleanup - remove tmp barcode file
                unlink($barcodePdfFile);
            } else {
                // If the LPA is not completed then stamp with the draft watermark
                $draftWatermarkPdf = $this->getTemplatePdfFilePath('RegistrationWatermark.pdf');

                $this->stampPageWith($draftWatermarkPdf, 16);
                $this->stampPageWith($draftWatermarkPdf, 17);
                $this->stampPageWith($draftWatermarkPdf, 18);
                $this->stampPageWith($draftWatermarkPdf, 19);
                $this->stampPageWith($draftWatermarkPdf, 20);
            }

            // Protect the PDF with a password now
            $this->protectPdf();
        }

        return $pdfFile;
    }

    /**
     * Apply the required stamp to the specified page (and possibly any inserted pages)
     * by creating a new copy of the PDF
     *
     * @param string $stampPdf File path of PDF to use as the stamp
     * @param int $pageNumber
     * @param bool $stampInsertedPages
     *
     * @return void
     */
    private function stampPageWith(string $stampPdf, int $pageNumber, bool $stampInsertedPages = true): void
    {
        // Create a copy of the LPA PDF with the contents of the provided PDF stamped on the specified page
        $tmpStampedPdfName = $this->getIntermediatePdfFilePath('stamp.pdf');
        $stampedPdfAllPages = $this->pdftkFactory->create($this->pdfFile);
        $stampedPdfAllPages->stamp($stampPdf)
            ->flatten()
            ->saveAs($tmpStampedPdfName);

        $newPdf = $this->pdftkFactory->create([
            'A' => $this->pdfFile,
            'B' => $tmpStampedPdfName
        ]);

        // Adjust the page number to account for the page shift - but only for the pages before this one
        $pageNumberShifted = $pageNumber + $this->getPageShiftBeforePage($pageNumber);

        // If we are going to stamp the inserted pages too then determine the end page to use in the stamped PDF
        // Initially set the end page to the same as the start page so only a single page will be inserted
        $stampedPdfEndPage = $pageNumberShifted;

        if ($stampInsertedPages) {
            // Determine how many pages of the stamped PDF to use by inspecting the shifted position
            // of the next page (if available)
            $nextPage = $pageNumber + 1;

            // If the current page is the last page then set the number of pages to null so the code below knows
            // to use all trailing pages to the end of the document
            if ($nextPage > $this->numberOfPages) {
                $stampedPdfEndPage = 'end';
            } else {
                $stampedPdfEndPage = $nextPage + $this->getPageShiftBeforePage($nextPage) - 1;
            }
        }

        $newPdf->cat(1, $pageNumberShifted - 1, 'A')
            ->cat($pageNumberShifted, $stampedPdfEndPage, 'B');

        // If the stamped end page is numeric then that means this was NOT the last page and therefore we need
        // to append another unstamped part of the PDF
        if (is_numeric($stampedPdfEndPage)) {
            $newPdf->cat($stampedPdfEndPage + 1, 'end', 'A');
        }

        $newPdf->flatten()
            ->saveAs($this->pdfFile);

        // Remove the temp PDF with all the pages stamped
        if (file_exists($tmpStampedPdfName)) {
            unlink($tmpStampedPdfName);
        }
    }
}
