<?php

namespace Opg\Lpa\Pdf;

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
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\Pdf\Aggregator\ContinuationSheet1 as ContinuationSheet1Aggregator;
use Opg\Lpa\Pdf\Aggregator\ContinuationSheet2 as ContinuationSheet2Aggregator;
use Opg\Lpa\Pdf\Traits\LongContentTrait;
use Exception;
use mikehaertl\pdftk\Pdf as Pdftk;
use Zend\Barcode\Barcode;

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
    const MAX_ATTORNEYS_PER_PAGE_SECTION_11 = 4;
    const MAX_APPLICANTS_SECTION_12 = 4;
    const MAX_SIGNATURES_SECTION_15 = 4;

    /**
     * PDF file name for the coversheet
     *
     * @var
     */
    protected $coversheetFileName;

    /**
     * PDF file name for the draft coversheet
     *
     * @var
     */
    protected $coversheetFileNameDraft;

    /**
     * Flag to indicate if the LPA should be considered completed or not - assume not until proven otherwise
     *
     * @var bool
     */
    private $lpaIsComplete = false;

    /**
     * @param Lpa|null $lpa
     * @param array $options
     * @throws Exception
     */
    public function __construct(Lpa $lpa = null, array $options = [])
    {
        //  Check that the coversheet variables have been set
        if (is_null($this->coversheetFileName)) {
            throw new Exception('PDF coversheet file name must be defined to create an LP1');
        }

        //  If an LPA has been provided - check that it has been completed
        if ($lpa instanceof Lpa) {
            $stateChecker = new StateChecker($lpa);
            $this->lpaIsComplete = $stateChecker->isStateCompleted();
        }

        parent::__construct($lpa, $options);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Add an appropriate coversheet to the start of the document
        $this->insertStaticPDF($this->lpaIsComplete ? $this->coversheetFileName : $this->coversheetFileNameDraft, 1, 2, 'start');

        $this->populatePageOne($lpa->document->donor);
        $this->populatePageTwoThreeFour($lpa->document);
        $this->populatePageFive($lpa->document);
        $this->populatePageSix($lpa->document->primaryAttorneyDecisions);
        $this->populatePageSeven($lpa->document->peopleToNotify);
        $this->populatePageEight($lpa->document);
        //  No content on page 9
        $this->populatePageTen($lpa->document->donor);
        $this->populatePageEleven($lpa->document->certificateProvider);
        $this->populatePageTwelveThirteenFourteenFifthteen($lpa);
        //  No content on page 16
        $this->populatePageSeventeen($lpa->document);
        $this->populatePageEighteen($lpa->document->correspondent);
        $this->populatePageNineteen($lpa->payment, $lpa->repeatCaseNumber);
        $this->populatePageTwenty($lpa->document->whoIsRegistering);

        //  Add any continuation sheets - this must take place AFTER the core content is set above to ensure that pages are inserted in the correct order
        $this->addContinuationSheets($lpa);

        $this->setFooterContent($lpa);
    }

    /**
     * @param Donor $donor
     */
    private function populatePageOne(Donor $donor)
    {
        $this->setData('lpa-document-donor-name-title', $donor->name->title)
             ->setData('lpa-document-donor-name-first', $donor->name->first)
             ->setData('lpa-document-donor-name-last', $donor->name->last)
             ->setData('lpa-document-donor-otherNames', $donor->otherNames)
             ->setData('lpa-document-donor-dob-date-day', $donor->dob->date->format('d'))
             ->setData('lpa-document-donor-dob-date-month', $donor->dob->date->format('m'))
             ->setData('lpa-document-donor-dob-date-year', $donor->dob->date->format('Y'))
             ->setData('lpa-document-donor-address-address1', $donor->address->address1)
             ->setData('lpa-document-donor-address-address2', $donor->address->address2)
             ->setData('lpa-document-donor-address-address3', $donor->address->address3)
             ->setData('lpa-document-donor-address-postcode', $donor->address->postcode);

        if ($donor->email instanceof EmailAddress) {
            $this->setData('lpa-document-donor-email-address', $donor->email->address);
        }
    }

    /**
     * @param Document $lpaDocument
     */
    private function populatePageTwoThreeFour(Document $lpaDocument)
    {
        $primaryAttorneys = $this->getOrderedAttorneys($lpaDocument->primaryAttorneys);

        for ($i = 0; $i < self::MAX_ATTORNEYS_SECTION_2; $i++) {
            //  If there is a primary attorney for this index then render the details
            if (array_key_exists($i, $primaryAttorneys)) {
                $primaryAttorney = $primaryAttorneys[$i];

                if ($primaryAttorney instanceof TrustCorporation) {
                    $this->setCheckBox('attorney-' . $i . '-is-trust-corporation')
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', (string) $primaryAttorney->name);
                } else {
                    $this->setData('lpa-document-primaryAttorneys-' . $i . '-name-title', $primaryAttorney->name->title)
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-name-first', $primaryAttorney->name->first)
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-name-last', $primaryAttorney->name->last)
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-dob-date-day', $primaryAttorney->dob->date->format('d'))
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-dob-date-month', $primaryAttorney->dob->date->format('m'))
                         ->setData('lpa-document-primaryAttorneys-' . $i . '-dob-date-year', $primaryAttorney->dob->date->format('Y'));
                }

                $this->setData('lpa-document-primaryAttorneys-' . $i . '-address-address1', $primaryAttorney->address->address1)
                     ->setData('lpa-document-primaryAttorneys-' . $i . '-address-address2', $primaryAttorney->address->address2)
                     ->setData('lpa-document-primaryAttorneys-' . $i . '-address-address3', $primaryAttorney->address->address3)
                     ->setData('lpa-document-primaryAttorneys-' . $i . '-address-postcode', $primaryAttorney->address->postcode);

                if ($primaryAttorney->email instanceof EmailAddress) {
                    $this->setData('lpa-document-primaryAttorneys-' . $i . '-email-address', $primaryAttorney->email->address, true);
                }
            } else {
                //  Add a strike through on the appropriate page
                $strikeThroughArea = 'primaryAttorney-' . $i;

                //  Determine what page number this is
                $strikeThroughPage = 2 + floor($i/2);

                if ($strikeThroughPage == 2) {
                    //  Add the required strike through area prefix
                    $strikeThroughArea .= '-' . $this->getStrikeThroughSuffix();
                }

                $this->addStrikeThrough($strikeThroughArea, $strikeThroughPage);
            }
        }

        if (count($primaryAttorneys) > self::MAX_ATTORNEYS_SECTION_2) {
            $this->setCheckBox('has-more-than-4-attorneys');
        }

        //  Set the attorney decisions value
        if (count($primaryAttorneys) == 1) {
            $this->setData('how-attorneys-act', 'only-one-attorney-appointed');
        } elseif ($lpaDocument->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $this->setData('how-attorneys-act', $lpaDocument->primaryAttorneyDecisions->how);
        }
    }

    /**
     * @param Document $lpaDocument
     */
    private function populatePageFive(Document $lpaDocument)
    {
        $replacementAttorneys = $this->getOrderedAttorneys($lpaDocument->replacementAttorneys);

        for ($i = 0; $i < self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4; $i++) {
            //  If there is a replacement attorney for this index then render the details
            if (array_key_exists($i, $replacementAttorneys)) {
                $replacementAttorney = $replacementAttorneys[$i];

                if ($replacementAttorney instanceof TrustCorporation) {
                    $this->setCheckBox('replacement-attorney-' . $i . '-is-trust-corporation')
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-name-last', (string) $replacementAttorney->name);
                } else {
                    $this->setData('lpa-document-replacementAttorneys-' . $i . '-name-title', $replacementAttorney->name->title)
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-name-first', $replacementAttorney->name->first)
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-name-last', $replacementAttorney->name->last)
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-dob-date-day', $replacementAttorney->dob->date->format('d'))
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-dob-date-month', $replacementAttorney->dob->date->format('m'))
                         ->setData('lpa-document-replacementAttorneys-' . $i . '-dob-date-year', $replacementAttorney->dob->date->format('Y'));
                }

                $this->setData('lpa-document-replacementAttorneys-' . $i . '-address-address1', $replacementAttorney->address->address1)
                     ->setData('lpa-document-replacementAttorneys-' . $i . '-address-address2', $replacementAttorney->address->address2)
                     ->setData('lpa-document-replacementAttorneys-' . $i . '-address-address3', $replacementAttorney->address->address3)
                     ->setData('lpa-document-replacementAttorneys-' . $i . '-address-postcode', $replacementAttorney->address->postcode);

                if ($replacementAttorney->email instanceof EmailAddress) {
                    $this->setData('lpa-document-replacementAttorneys-' . $i . '-email-address', $replacementAttorney->email->address, true);
                }
            } else {
                //  Add a strike through on the appropriate page
                $strikeThroughArea = 'replacementAttorney-' . $i . '-' . $this->getStrikeThroughSuffix();
                $strikeThroughPage = 5 + floor($i/2);

                $this->addStrikeThrough($strikeThroughArea, $strikeThroughPage);
            }
        }

        if (count($replacementAttorneys) > self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4) {
            $this->setCheckBox('has-more-than-2-replacement-attorneys');
        }

        //  Determine whether to check the when/how my attorneys can act checkbox
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
     */
    private function populatePageSeven(array $peopleToNotify)
    {
        for ($i = 0; $i < self::MAX_PEOPLE_TO_NOTIFY_SECTION_6; $i++) {
            //  If there is a person to notify for this index then render the details
            if (array_key_exists($i, $peopleToNotify)) {
                $personToNotify = $peopleToNotify[$i];

                $this->setData('lpa-document-peopleToNotify-' . $i . '-name-title', $personToNotify->name->title)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-name-first', $personToNotify->name->first)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-name-last', $personToNotify->name->last)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-address-address1', $personToNotify->address->address1)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-address-address2', $personToNotify->address->address2)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-address-address3', $personToNotify->address->address3)
                     ->setData('lpa-document-peopleToNotify-' . $i . '-address-postcode', $personToNotify->address->postcode);
            } else {
                //  Add a strike through on the appropriate page
                $this->addStrikeThrough('people-to-notify-' . $i, 7);
            }
        }

        if (count($peopleToNotify) > self::MAX_PEOPLE_TO_NOTIFY_SECTION_6) {
            //  TODO - Historic bug - the check box on the H&W PDF is named incorrecly
            $this->setCheckBox('has-more-than-4-notified-people')   //Property and Finance
                 ->setCheckBox('has-more-than-5-notified-people');  //Health and Welfare
        }
    }

    /**
     * @param Document $lpaDocument
     */
    private function populatePageEight(Document $lpaDocument)
    {
        $details = [
            'preference'  => 'has-more-preferences',
            'instruction' => 'has-more-instructions',
        ];

        foreach ($details as $dataKey => $hasMoreKey) {
            $detailString = $lpaDocument->$dataKey;

            if (empty($detailString)) {
                $this->addStrikeThrough($dataKey, 8);
            } else {
                $this->setData('lpa-document-' . $dataKey, $this->getInstructionsAndPreferencesContent($detailString));

                if ($this->fillsInstructionsPreferencesBox($detailString)) {
                    $this->setCheckBox($hasMoreKey);
                }
            }
        }
    }

    /**
     * @param Donor $donor
     */
    private function populatePageTen(Donor $donor)
    {
        if ($donor->canSign === false) {
            $this->setData('see_continuation_sheet_3', 'see continuation sheet 3');
        }
    }

    /**
     * @param CertificateProvider $certificateProvider
     */
    private function populatePageEleven(CertificateProvider $certificateProvider)
    {
        $this->setData('lpa-document-certificateProvider-name-title', $certificateProvider->name->title)
             ->setData('lpa-document-certificateProvider-name-first', $certificateProvider->name->first)
             ->setData('lpa-document-certificateProvider-name-last', $certificateProvider->name->last)
             ->setData('lpa-document-certificateProvider-address-address1', $certificateProvider->address->address1)
             ->setData('lpa-document-certificateProvider-address-address2', $certificateProvider->address->address2)
             ->setData('lpa-document-certificateProvider-address-address3', $certificateProvider->address->address3)
             ->setData('lpa-document-certificateProvider-address-postcode', $certificateProvider->address->postcode);
    }

    /**
     * @param Lpa $lpa
     * @param int $pageIteration
     */
    private function populatePageTwelveThirteenFourteenFifthteen(Lpa $lpa, $pageIteration = 0)
    {
        //  This page is repeatable so determine which PDF object to use
        //  For the first MAX_ATTORNEYS_PER_PAGE_SECTION_11 number of pages we should populate the main document
        $pdf = ($pageIteration >= self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 ? new $this() : $this);

        //  Immediately get an array of all attorneys excluding trusts so we can work with it below
        $attorneys = array_merge($lpa->document->primaryAttorneys, $lpa->document->replacementAttorneys);

        foreach ($attorneys as $i => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                unset($attorneys[$i]);
                $attorneys = array_values($attorneys);
                break;
            }
        }

        //  Try to get the attorney for this page
        $attorney = null;

        if (array_key_exists($pageIteration, $attorneys)) {
            $attorney = $attorneys[$pageIteration];
        }

        //  Populate the page details for a human attorney
        if ($attorney instanceof Human) {
            //  Determine which index key ref to use - for the first MAX_ATTORNEYS_PER_PAGE_SECTION_11 number of pages iterate up
            //  But stop at MAX_ATTORNEYS_PER_PAGE_SECTION_11 - 1
            $dataKeyIndex = min($pageIteration, self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 - 1);

            $pdf->setData('signature-attorney-' . $dataKeyIndex . '-name-title', $attorney->name->title)
                ->setData('signature-attorney-' . $dataKeyIndex . '-name-first', $attorney->name->first)
                ->setData('signature-attorney-' . $dataKeyIndex . '-name-last', $attorney->name->last);
        } else {
            //  Add a strike through on the appropriate page
            $pageNumber = 12 + $pageIteration;
            $strikeThroughArea = 'attorney-signature-' . $this->getStrikeThroughSuffix();
            $pdf->addStrikeThrough($strikeThroughArea, $pageNumber);
        }

        //  Set the fotter content too
        $pdf->setFooterContent($lpa);

        //  If applicable add the page PDF as a constituent
        if ($pdf !== $this) {
            //  We will be using page 15 for this purpose to make sure the PDF page numbering is sequential
            $this->addConstituentPdfPage($pdf, 15, 15);
        }

        //  If there is another attorney available, or we are still populating the first MAX_ATTORNEYS_PER_PAGE_SECTION_11 number of pages, trigger again
        $pageIteration++;

        if ($pageIteration < self::MAX_ATTORNEYS_PER_PAGE_SECTION_11 || array_key_exists($pageIteration, $attorneys)) {
            $this->populatePageTwelveThirteenFourteenFifthteen($lpa, $pageIteration);
        }
    }

    /**
     * @param Document $lpaDocument
     * @param int $pageIteration
     */
    private function populatePageSeventeen(Document $lpaDocument, $pageIteration = 0)
    {
        //  This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new $this() : $this);

        $applicantType = 'donor';
        $strikeThroughIndex = 0;

        if (is_array($lpaDocument->whoIsRegistering)) {
            $applicantType = 'attorney';

            $attorneysForPages = array_chunk($lpaDocument->whoIsRegistering, self::MAX_APPLICANTS_SECTION_12);

            if (array_key_exists($pageIteration, $attorneysForPages)) {
                $attorneysForPage = $attorneysForPages[$pageIteration];

                //  Insert the specific attorney details
                foreach ($attorneysForPage as $i => $attorneyId) {
                    $attorney = $lpaDocument->getPrimaryAttorneyById($attorneyId);

                    if ($attorney instanceof TrustCorporation) {
                        $pdf->setData('applicant-' . $i . '-name-last', $attorney->name);
                    } else {
                        $pdf->setData('applicant-' . $i . '-name-title', $attorney->name->title)
                            ->setData('applicant-' . $i . '-name-first', $attorney->name->first)
                            ->setData('applicant-' . $i . '-name-last', $attorney->name->last)
                            ->setData('applicant-' . $i . '-dob-date-day', $attorney->dob->date->format('d'))
                            ->setData('applicant-' . $i . '-dob-date-month', $attorney->dob->date->format('m'))
                            ->setData('applicant-' . $i . '-dob-date-year', $attorney->dob->date->format('Y'));
                    }

                    $strikeThroughIndex++;
                }

                //  If applicable add the page PDF as a constituent
                if ($pdf !== $this) {
                    $this->addConstituentPdfPage($pdf, 17, 17);
                }

                //  If there is another page available trigger again
                $pageIteration++;

                if (array_key_exists($pageIteration, $attorneysForPages)) {
                    $this->populatePageSeventeen($lpaDocument, $pageIteration);
                }
            }
        }

        $pdf->setData('who-is-applicant', $applicantType);

        //  Draw the strike throughs
        while ($strikeThroughIndex < self::MAX_APPLICANTS_SECTION_12) {
            $areaReference = 'applicant-' . $strikeThroughIndex . '-' . $this->getStrikeThroughSuffix();
            $pdf->addStrikeThrough($areaReference, 17);
            $strikeThroughIndex++;
        }
    }

    /**
     * @param Correspondence $correspondent
     */
    private function populatePageEighteen(Correspondence $correspondent = null)
    {
        if (!is_null($correspondent)) {
            //  Set the correspondent type - if this is the certificate provider then set it to other
            $correspondentType = ($correspondent->who == Correspondence::WHO_CERTIFICATE_PROVIDER ? Correspondence::WHO_OTHER : $correspondent->who);
            $this->setData('who-is-correspondent', $correspondentType);

            //  Display the name details
            if ($correspondent->who == Correspondence::WHO_DONOR) {
                //  No need to display the name for the donor
                $this->addStrikeThrough('correspondent-empty-name-address', 18);
            } else {
                if ($correspondent->name instanceof LongName) {
                    $this->setData('lpa-document-correspondent-name-title', $correspondent->name->title)
                        ->setData('lpa-document-correspondent-name-first', $correspondent->name->first)
                        ->setData('lpa-document-correspondent-name-last', $correspondent->name->last);
                }

                $this->setData('lpa-document-correspondent-company', (isset($correspondent->company) ? isset($correspondent->company) : ''));

                //  If the correspondent is an attorney then strike through the address field
                if ($correspondent->who == Correspondence::WHO_ATTORNEY) {
                    $this->addStrikeThrough('correspondent-empty-address', 18);
                } else {
                    //  The correspondent is "other" so display the full address
                    $this->setData('lpa-document-correspondent-address-address1', $correspondent->address->address1)
                        ->setData('lpa-document-correspondent-address-address2', $correspondent->address->address2)
                        ->setData('lpa-document-correspondent-address-address3', $correspondent->address->address3)
                        ->setData('lpa-document-correspondent-address-postcode', $correspondent->address->postcode);
                }
            }

            //  Set the contact preferences
            if ($correspondent->contactByPost === true) {
                $this->setCheckBox('correspondent-contact-by-post');
            }

            if ($correspondent->phone instanceof PhoneNumber) {
                $this->setCheckBox('correspondent-contact-by-phone')
                    ->setData('lpa-document-correspondent-phone-number', str_replace(" ", "", $correspondent->phone->number));
            }

            if ($correspondent->email instanceof EmailAddress) {
                $this->setCheckBox('correspondent-contact-by-email')
                    ->setData('lpa-document-correspondent-email-address', $correspondent->email->address);
            }

            if ($correspondent->contactInWelsh === true) {
                $this->setCheckBox('correspondent-contact-in-welsh');
            }
        }
    }

    /**
     * @param Payment|null $payment
     * @param null $repeatCaseNumber
     */
    private function populatePageNineteen(Payment $payment = null, $repeatCaseNumber = null)
    {
        if ($payment instanceof Payment) {
            if ($payment->method) {
                $this->setData('pay-by', $payment->method);
            }

            if ($payment->method == Payment::PAYMENT_TYPE_CARD) {
                $this->setData('lpa-payment-phone-number', 'NOT REQUIRED.');
            }

            if (($payment->reducedFeeReceivesBenefits && $payment->reducedFeeAwardedDamages)
                || $payment->reducedFeeLowIncome
                || $payment->reducedFeeUniversalCredit) {

                $this->setCheckBox('apply-for-fee-reduction');
            }

            //  Set any online payment details
            if (!is_null($payment->reference)) {
                $this->setData('lpa-payment-reference', $payment->reference)
                     ->setData('lpa-payment-amount', '£' . sprintf('%.2f', $payment->amount))
                     ->setData('lpa-payment-date-day', $payment->date->format('d'))
                     ->setData('lpa-payment-date-month', $payment->date->format('m'))
                     ->setData('lpa-payment-date-year', $payment->date->format('Y'));
            }
        }

        //  Set repeat application details
        if (!is_null($repeatCaseNumber)) {
            $this->setCheckBox('is-repeat-application')
                 ->setData('repeat-application-case-number', $repeatCaseNumber);
        }
    }

    /**
     * @param $whoIsRegistering
     * @param int $pageIteration
     */
    private function populatePageTwenty($whoIsRegistering, $pageIteration = 0)
    {
        //  This page is repeatable so determine which PDF object to use
        $pdf = ($pageIteration > 0 ? new $this() : $this);

        //  There must always be at least one signature
        $strikeThroughIndex = 1;

        if (is_array($whoIsRegistering)) {
            $signaturesForPages = array_chunk($whoIsRegistering, self::MAX_SIGNATURES_SECTION_15);

            if (array_key_exists($pageIteration, $signaturesForPages)) {
                $signaturesForPage = $signaturesForPages[$pageIteration];

                $strikeThroughIndex = count($signaturesForPage);

                //  If applicable add the page PDF as a constituent
                if ($pdf !== $this) {
                    //  Insert the page at the end of the document
                    $this->addConstituentPdfPage($pdf, 20, 'end');
                }

                //  If there is another page available trigger again
                $pageIteration++;

                if (array_key_exists($pageIteration, $signaturesForPages)) {
                    $this->populatePageTwenty($whoIsRegistering, $pageIteration);
                }
            }
        }

        //  Draw the strike throughs
        while ($strikeThroughIndex < self::MAX_SIGNATURES_SECTION_15) {
            $pdf->addStrikeThrough('applicant-signature-' . $strikeThroughIndex, 20);
            $strikeThroughIndex++;
        }
    }

    /**
     * @return string
     */
    abstract protected function getStrikeThroughSuffix();

    /**
     * @param Lpa $lpa
     */
    private function setFooterContent(Lpa $lpa)
    {
        $footerContentRef = ($lpa->document->type == Document::LPA_TYPE_PF ? 'lp1f' : 'lp1h');

        $this->setFooter('footer-instrument-right', $footerContentRef);
        $this->setFooter('footer-registration-right', $footerContentRef . '-draft');
    }

    /**
     * @param Lpa $lpa
     */
    private function addContinuationSheets(Lpa $lpa)
    {
        $continuationSheetsAdded = false;

        //  Add continuation sheet 1 instances if required
        $primaryAttorneys = $this->getOrderedAttorneys($lpa->document->primaryAttorneys);
        $primaryAttorneys = array_splice($primaryAttorneys, self::MAX_ATTORNEYS_SECTION_2);
        $replacementAttorneys = $this->getOrderedAttorneys($lpa->document->replacementAttorneys);
        $replacementAttorneys = array_splice($replacementAttorneys, self::MAX_REPLACEMENT_ATTORNEYS_SECTION_4);
        $peopleToNotify = array_splice($lpa->document->peopleToNotify, self::MAX_PEOPLE_TO_NOTIFY_SECTION_6);

        if (!empty($primaryAttorneys) || !empty($replacementAttorneys) || !empty($peopleToNotify)) {
            $continuationSheet1 = new ContinuationSheet1Aggregator($lpa, $primaryAttorneys, $replacementAttorneys, $peopleToNotify);
            $this->addConstituentPdf($continuationSheet1, 1, $continuationSheet1->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 2 (primary attorney decisions) instances if required
        if ($lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $continuationSheet2 = new ContinuationSheet2Aggregator($lpa, ContinuationSheet2::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS);
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 2 (how replacement attorneys can act) instances if required
        $replacementAttorneysContent = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        if (!empty($replacementAttorneysContent)) {
            $continuationSheet2 = new ContinuationSheet2Aggregator($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN);
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 2 (preferences) instances if required
        if ($this->fillsInstructionsPreferencesBox($lpa->document->preference)) {
            $continuationSheet2 = new ContinuationSheet2Aggregator($lpa, ContinuationSheet2::CS2_TYPE_PREFERENCES);
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 2 (instructions) instances if required
        if ($this->fillsInstructionsPreferencesBox($lpa->document->instruction)) {
            $continuationSheet2 = new ContinuationSheet2Aggregator($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS);
            $this->addConstituentPdf($continuationSheet2, 1, $continuationSheet2->getPageCount(), 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 3 instances if required
        if ($lpa->document->donor->canSign === false) {
            $continuationSheet3 = new ContinuationSheet3($lpa);
            $this->addConstituentPdf($continuationSheet3, 1, 2, 15);

            $continuationSheetsAdded = true;
        }

        //  Add continuation sheet 4 instances if required
        $attorneys = array_merge($lpa->document->primaryAttorneys, $lpa->document->replacementAttorneys);

        if ($this->getTrustAttorney($attorneys) instanceof TrustCorporation) {
            $continuationSheet4 = new ContinuationSheet4($lpa);
            $this->addConstituentPdf($continuationSheet4, 1, 2, 15);

            $continuationSheetsAdded = true;
        }

        //  If any continuation sheets have been added append another blank page
        if ($continuationSheetsAdded) {
            $this->insertBlankPage(15);
        }
    }

    /**
     * Return a sorted (trust first) array of attorneys
     *
     * @param array $attorneys
     * @return array
     */
    private function getOrderedAttorneys(array $attorneys)
    {
        foreach ($attorneys as $i => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                //  Recreate the attorney array with the trust at the start
                unset($attorneys[$i]);
                $attorneys = array_values($attorneys);
                array_unshift($attorneys, $attorney);
                break;
            }
        }

        return $attorneys;
    }

    /**
     * @param array $attorneys
     * @return mixed|null
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
     * @return string
     */
    public function generate($protect = false)
    {
        //  Generate the LP1 PDF
        $pdfFile = parent::generate($protect);

        //  Only perform the stamping if this is the main PDF being generated rather than a constituent PDF
        if (!is_null($this->formattedLpaRef)) {
            //  If the LPA is completed then stamp it with a barcode
            if ($this->lpaIsComplete) {
                // Generate the barcode
                $renderer = Barcode::factory(
                    'code39',
                    'pdf',
                    [
                        'text' => $this->formattedLpaRef,
                        'drawText' => false,
                        'factor' => 2,
                        'barHeight' => 25,
                    ],
                    [
                        'topOffset' => 789,
                        'leftOffset' => 40,
                    ]
                );

                //  Create a blank PDF with the barcode only
                $barcodeOnlyPdf = $renderer->draw();
                $barcodePdfFile = $this->getIntermediatePdfFilePath('barcode.pdf');
                $barcodeOnlyPdf->save($barcodePdfFile);

                //  Stamp the required page with the new barcode using the unshifted page number
                $this->stampPageWith($barcodePdfFile, 19);

                //  Cleanup - remove tmp barcode file
                unlink($barcodePdfFile);
            } else {
                //  If the LPA is not completed then stamp with the draft watermark
                $draftWatermarkPdf = $this->getTemplatePdfFilePath('RegistrationWatermark.pdf');

                $this->stampPageWith($draftWatermarkPdf, 16);
                $this->stampPageWith($draftWatermarkPdf, 17);
                $this->stampPageWith($draftWatermarkPdf, 18);
                $this->stampPageWith($draftWatermarkPdf, 19);
                $this->stampPageWith($draftWatermarkPdf, 20);
            }
        }

        return $pdfFile;
    }

    /**
     * Apply the required stamp to the specified page by creating a new copy of the PDF
     *
     * @param $stampPdf
     * @param $pageNumber
     */
    private function stampPageWith($stampPdf, $pageNumber)
    {
        //  Create a copy of the LPA PDF with the barcode stamped on it on all pages so we can extract the required page later
        $tmpStampPdfName = $this->getIntermediatePdfFilePath('stamp.pdf');
        $pdfStampedAllPages = new Pdftk($this->pdfFile);
        $pdfStampedAllPages->stamp($stampPdf)
                           ->flatten()
                           ->saveAs($tmpStampPdfName);

        $newPdf = new Pdftk([
            'A' => $this->pdfFile,
            'B' => $tmpStampPdfName
        ]);

        //  Account for the page shift from previous PDF generation
        $pageNumber = $pageNumber + $this->pageShift;

        $newPdf->cat(1, $pageNumber - 1, 'A')
               ->cat($pageNumber, null, 'B');

        //  If the last page is being stamped then do not append any more pages
        if (($pageNumber - $this->pageShift) < $this->numberOfPages) {
            $newPdf->cat($pageNumber + 1, 'end', 'A');
        }

        $newPdf->flatten()
               ->saveAs($this->pdfFile);

        //  Remove the temp PDF with all the pages stamped
        if (file_exists($tmpStampPdfName)) {
            unlink($tmpStampPdfName);
        }
    }
}
