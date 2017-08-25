<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Zend\Barcode\Barcode;
use mikehaertl\pdftk\Pdf;
use RuntimeException;

abstract class AbstractLp1 extends AbstractTopForm
{
    /**
     * PDFTK pdf object
     *
     * @var Pdf
     */
    protected $pdf;

    /**
     * Store cross line strokes parameters.
     * The array index is the page number of pdf document,
     * and value is array of cross line param keys.
     *
     * @var array
     */
    protected $drawingTargets = array();

    /**
     * There or not the registration section of teh LPA is complete
     *
     * @var bool
     */
    private $registrationIsComplete;

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        $stateChecker = new StateChecker($lpa);

        //  Check that the document can be created
        if (!$stateChecker->canGenerateLP1()) {
            throw new RuntimeException('LPA does not contain all the required data to generate a LP1');
        }

        $this->registrationIsComplete = $stateChecker->isStateCompleted();
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file
     *
     * @return $this
     */
    public function generate()
    {
        $this->logGenerationStatement();

        //  Generate the standard form
        $this->logGenerationStatement('Standard Form');

        // register a random generated temp file path, and store it $interFileStack
        $filePath = $this->registerTempFile('LP1');

        // populate form data and generate pdf
        $pdf = $this->getPdfObject();
        $pdf->fillForm($this->dataMapping())
            ->flatten()
            ->saveAs($filePath);

        // If registration is complete add the tracking barcode
        if ($this->registrationIsComplete) {
            $this->addLpaIdBarcode($filePath);
        }

        // draw cross lines if there's any blank slot
        if (!empty($this->drawingTargets)) {
            $this->drawCrossLines($filePath, $this->drawingTargets);
        }

        //  Generate the additional pages - using the functions in descendant classes
        $this->generateAdditionalPages();

        //  Generate coversheets
        $this->logGenerationStatement('Coversheets');

        //  Instantiate and generate the correct coversheet
        $coversheet = ($this->registrationIsComplete ? new CoversheetRegistration($this->lpa) : new CoversheetInstrument($this->lpa));
        $coversheet = $coversheet->generate();

        $this->mergerIntermediateFilePaths($coversheet);

        $this->mergePdfs();
        $this->protectPdf();

        return $this;
    }

    /**
     * Add an LPA ID barcode to the file specified
     *
     * @param $filePath
     */
    private function addLpaIdBarcode($filePath)
    {
        // Generate the barcode
        // Zero pad the ID, and prepend the 'A'
        $formattedLpaId = 'A' . sprintf("%011d", $this->lpa->id);

        $renderer = Barcode::factory(
            'code39',
            'pdf',
            [
                'text' => $formattedLpaId,
                'drawText' => false,
                'factor' => 2,
                'barHeight' => 25,
            ],
            [
                'topOffset' => 789,
                'leftOffset' => 40,
            ]
        );

        $imageResource = $renderer->draw();

        //  TODO - Try not to use getTmpFilePath here so we can condense that down...
        $barcodeTmpFile = $this->getTmpFilePath('barcode');

        // Save to temporary file...
        $imageResource->save($barcodeTmpFile);

        // Merge the barcode into the page
        // Take a copy of the PDF to work with
        $pdfWithBarcode = new Pdf($filePath);

        // Pull out the page the barcode is appended to
        $pdfWithBarcode->cat(19);

        // Add the barcode to the page
        $pdfWithBarcode = new Pdf($pdfWithBarcode);
        $pdfWithBarcode->stamp($barcodeTmpFile);

        // Re-integrate the page into the full PDF
        $pdf = new Pdf();

        $pdf->addFile($filePath, 'A');
        $pdf->addFile($pdfWithBarcode, 'B');

        // Swap out page 19 for the one with the barcode
        $pdf->cat(1, 18, 'A');
        $pdf->cat(1, null, 'B');
        $pdf->cat(20, 'end', 'A');

        $pdf->flatten()
            ->saveAs($filePath);

        //  Cleanup - remove tmp barcode file
        unlink($barcodeTmpFile);
    }

    /**
     * Generate additional pages depending on the LPA's composition
     */
    private function generateAdditionalPages()
    {
        $this->logGenerationStatement('Additional Pages');

        // generate CS1
        if (count($this->lpa->document->primaryAttorneys) > self::MAX_ATTORNEYS_ON_STANDARD_FORM
            || count($this->lpa->document->replacementAttorneys) > self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM
            || count($this->lpa->document->peopleToNotify) > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {

            $cs1 = new Cs1($this->lpa);
            $generatedCs1 = $cs1->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }

        // generate a CS2 page if how attorneys making decisions depends on a special arrangement
        if ($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $cs2 = new Cs2PrimaryAttorneyDecisions($this->lpa);
            $generatedCs2 = $cs2->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }

        //  Determine if the replacement attorney continuation sheet should be created
        $createReplacementAttorneyCs2 = false;

        if ((count($this->lpa->document->primaryAttorneys) == 1
            || (count($this->lpa->document->primaryAttorneys) > 1
                && $this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY))
            && count($this->lpa->document->replacementAttorneys) > 1) {

            $createReplacementAttorneyCs2 = in_array($this->lpa->document->replacementAttorneyDecisions->how, [
                ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS
            ]);
        } elseif (count($this->lpa->document->primaryAttorneys) > 1 && $this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
            if (count($this->lpa->document->replacementAttorneys) == 1) {
                $createReplacementAttorneyCs2 = in_array($this->lpa->document->replacementAttorneyDecisions->how, [
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
                    ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
                ]);
            } elseif (count($this->lpa->document->replacementAttorneys) > 1) {
                if ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                    $createReplacementAttorneyCs2 = ($this->lpa->document->replacementAttorneyDecisions->how != ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY);
                } elseif ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $createReplacementAttorneyCs2 = true;
                }
            }
        }

        if ($createReplacementAttorneyCs2) {
            $cs2 = new Cs2ReplacementAttorneys($this->lpa);
            $generatedCs2 = $cs2->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }

        // generate a CS2 page if preference exceed available space on standard form
        if (!$this->canFitIntoTextBox($this->lpa->document->preference)) {
            $cs2 = new Cs2Preferences($this->lpa);
            $generatedCs2 = $cs2->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }

        // generate a CS2 page if instruction exceed available space on standard form
        if (!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
            $cs2 = new Cs2Instructions($this->lpa);
            $generatedCs2 = $cs2->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }

        // generate CS3 page if donor cannot sign on LPA
        if (false === $this->lpa->document->donor->canSign) {
            $cs3 = new Cs3($this->lpa);
            $generatedCs3 = $cs3->generate();
            $this->mergerIntermediateFilePaths($generatedCs3);
        }

        // CS4
        if ($this->hasTrustCorporation()) {
            $generatedCs4 = (new Cs4($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedCs4);
        }

        // if number of attorneys (including replacements) is greater than 4, duplicate Section 11 - Attorneys Signatures page
        // as many as needed to be able to fit all attorneys in the form.
        $totalAttorneys = count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys);

        if ($this->hasTrustCorporation()) {
            $totalAttorneys--;
        }

        if ($totalAttorneys > self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $generatedAdditionalAttorneySignaturePages = (new Lp1AdditionalAttorneySignaturePage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneySignaturePages);
        }

        $numOfApplicants = count($this->lpa->document->whoIsRegistering);

        // Section 12 - Applicants - If number of applicant is greater than 4, duplicate this page as many as needed in order to fit all applicants in
        if (is_array($this->lpa->document->whoIsRegistering) && $numOfApplicants > self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
            $lp1AdditionalApplicantPage = new Lp1AdditionalApplicantPage($this->lpa);
            $generatedAdditionalApplicantPages = $lp1AdditionalApplicantPage->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalApplicantPages);
        }

        // Section 15 - additional applicants signature
        if (is_array($this->lpa->document->whoIsRegistering) && $numOfApplicants > self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM) {
            $totalAdditionalApplicants = $numOfApplicants - self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
            $totalAdditionalApplicantPages = ceil($totalAdditionalApplicants / self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);
            if ($totalAdditionalApplicantPages > 0) {
                $lp1AdditionalApplicantSignaturePage = new Lp1AdditionalApplicantSignaturePage($this->lpa);
                $generatedAdditionalApplicantSignaturePages = $lp1AdditionalApplicantSignaturePage->generate();
                $this->mergerIntermediateFilePaths($generatedAdditionalApplicantSignaturePages);
            }
        }
    }

    /**
     * Set the data mappings for this form
     *
     * @return array
     */
    protected function dataMapping()
    {
        //  Donor section (section 1)
        $this->dataForForm['lpa-id'] = Formatter::id($this->lpa->id);
        $this->dataForForm['lpa-document-donor-name-title'] = $this->lpa->document->donor->name->title;
        $this->dataForForm['lpa-document-donor-name-first'] = $this->lpa->document->donor->name->first;
        $this->dataForForm['lpa-document-donor-name-last'] = $this->lpa->document->donor->name->last;
        $this->dataForForm['lpa-document-donor-otherNames'] = $this->lpa->document->donor->otherNames;
        $this->dataForForm['lpa-document-donor-dob-date-day'] = $this->lpa->document->donor->dob->date->format('d');
        $this->dataForForm['lpa-document-donor-dob-date-month'] = $this->lpa->document->donor->dob->date->format('m');
        $this->dataForForm['lpa-document-donor-dob-date-year'] = $this->lpa->document->donor->dob->date->format('Y');
        $this->dataForForm['lpa-document-donor-address-address1'] = $this->lpa->document->donor->address->address1;
        $this->dataForForm['lpa-document-donor-address-address2'] = $this->lpa->document->donor->address->address2;
        $this->dataForForm['lpa-document-donor-address-address3'] = $this->lpa->document->donor->address->address3;
        $this->dataForForm['lpa-document-donor-address-postcode'] = $this->lpa->document->donor->address->postcode;
        $this->dataForForm['lpa-document-donor-email-address'] = ($this->lpa->document->donor->email instanceof EmailAddress ? $this->lpa->document->donor->email->address : null);

        //  attorneys section (section 2)
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if ($noOfPrimaryAttorneys == 1) {
            $this->drawingTargets[2] = ['primaryAttorney-2', 'primaryAttorney-3'];
        } elseif ($noOfPrimaryAttorneys == 2) {
            $this->drawingTargets[2] = ['primaryAttorney-2', 'primaryAttorney-3'];
        } elseif ($noOfPrimaryAttorneys == 3) {
            $this->drawingTargets[2] = ['primaryAttorney-3'];
        }

        if ($noOfPrimaryAttorneys > 4) {
            $this->dataForForm['has-more-than-4-attorneys'] = self::CHECK_BOX_ON;
        }

        //  attorney decision section (section 3)
        if ($noOfPrimaryAttorneys == 1) {
            $this->dataForForm['how-attorneys-act'] = 'only-one-attorney-appointed';
        } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $this->dataForForm['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
        }

        //  replacement attorneys section (section 4)
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if ($noOfReplacementAttorneys > 2) {
            $this->dataForForm['has-more-than-2-replacement-attorneys'] = self::CHECK_BOX_ON;
        }

        // checkbox for replacement decisions are not taking the default arrangement
        if ($noOfPrimaryAttorneys == 1) {
            if ($noOfReplacementAttorneys > 1
                && ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                    || $this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS)) {

                $this->dataForForm['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
            }
        } elseif ($noOfPrimaryAttorneys > 1) {
            switch ($this->lpa->document->primaryAttorneyDecisions->how) {
                case PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    if ($noOfReplacementAttorneys == 1) {
                        if ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST
                            || $this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {

                            $this->dataForForm['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        }
                    } elseif ($noOfReplacementAttorneys > 1) {
                        if ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                            $this->dataForForm['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        } elseif ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                            if ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                                || $this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {

                                $this->dataForForm['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                            }
                        }
                    }

                    break;
                case PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    if ($noOfReplacementAttorneys > 1) {
                        if ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                            || $this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {

                            $this->dataForForm['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        }
                    }
                    break;
            }
        }

        //  People to notify (Section 6)
        $i = 0;

        foreach ($this->lpa->document->peopleToNotify as $peopleToNotify) {
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-name-title'] = $peopleToNotify->name->title;
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-name-first'] = $peopleToNotify->name->first;
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-name-last'] = $peopleToNotify->name->last;

            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-address-address1'] = $peopleToNotify->address->address1;
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-address-address2'] = $peopleToNotify->address->address2;
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-address-address3'] = $peopleToNotify->address->address3;
            $this->dataForForm['lpa-document-peopleToNotify-' . $i . '-address-postcode'] = $peopleToNotify->address->postcode;

            if (++$i == self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
                break;
            }
        }

        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if ($noOfPeopleToNotify > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            //Property and Finance
            $this->dataForForm['has-more-than-4-notified-people'] = self::CHECK_BOX_ON;
            //Health and Welfare
            $this->dataForForm['has-more-than-5-notified-people'] = self::CHECK_BOX_ON;
        }

        if ($noOfPeopleToNotify < self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->drawingTargets[6] = [];
            for ($i = self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $noOfPeopleToNotify; $i > 0; $i--) {
                $this->drawingTargets[6][] = 'people-to-notify-' . (self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $i);
            }
        }

        //  Preference and Instructions (Section 7)
        if (!empty((string)$this->lpa->document->preference)) {
            if (!$this->canFitIntoTextBox($this->lpa->document->preference)) {
                $this->dataForForm['has-more-preferences'] = self::CHECK_BOX_ON;
            }

            $this->dataForForm['lpa-document-preference'] = $this->getInstructionsAndPreferencesContent(0, $this->lpa->document->preference);
        } else {
            $this->drawingTargets[7] = ['preference'];
        }

        if (!empty((string)$this->lpa->document->instruction)) {
            if (!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
                $this->dataForForm['has-more-instructions'] = self::CHECK_BOX_ON;
            }

            $this->dataForForm['lpa-document-instruction'] = $this->getInstructionsAndPreferencesContent(0, $this->lpa->document->instruction);
        } else {
            $this->drawingTargets[7] = (isset($this->drawingTargets[7]) ? ['preference', 'instruction'] : ['instruction']);
        }

        //  Section 9 - Donor signature page
        if ($this->lpa->document->donor->canSign === false) {
            $this->dataForForm['see_continuation_sheet_3'] = 'see continuation sheet 3';
        }

        //  Populate certificate provider page (Section 10)
        $this->dataForForm['lpa-document-certificateProvider-name-title'] = $this->lpa->document->certificateProvider->name->title;
        $this->dataForForm['lpa-document-certificateProvider-name-first'] = $this->lpa->document->certificateProvider->name->first;
        $this->dataForForm['lpa-document-certificateProvider-name-last'] = $this->lpa->document->certificateProvider->name->last;

        $this->dataForForm['lpa-document-certificateProvider-address-address1'] = $this->lpa->document->certificateProvider->address->address1;
        $this->dataForForm['lpa-document-certificateProvider-address-address2'] = $this->lpa->document->certificateProvider->address->address2;
        $this->dataForForm['lpa-document-certificateProvider-address-address3'] = $this->lpa->document->certificateProvider->address->address3;
        $this->dataForForm['lpa-document-certificateProvider-address-postcode'] = $this->lpa->document->certificateProvider->address->postcode;

        //  Applicant (Section 12)
        if ($this->lpa->document->whoIsRegistering == 'donor') {
            $this->dataForForm['who-is-applicant'] = 'donor';
            $this->drawingTargets[19] = [
                'applicant-signature-1',
                'applicant-signature-2',
                'applicant-signature-3',
            ];
        } elseif (is_array($this->lpa->document->whoIsRegistering)) {
            $this->dataForForm['who-is-applicant'] = 'attorney';
            $i = 0;

            foreach ($this->lpa->document->whoIsRegistering as $attorneyId) {
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);

                if ($attorney instanceof TrustCorporation) {
                    $this->dataForForm['applicant-' . $i . '-name-last'] = $attorney->name;
                } else {
                    $this->dataForForm['applicant-' . $i . '-name-title'] = $attorney->name->title;
                    $this->dataForForm['applicant-' . $i . '-name-first'] = $attorney->name->first;
                    $this->dataForForm['applicant-' . $i . '-name-last'] = $attorney->name->last;
                    $this->dataForForm['applicant-' . $i . '-dob-date-day'] = $attorney->dob->date->format('d');
                    $this->dataForForm['applicant-' . $i . '-dob-date-month'] = $attorney->dob->date->format('m');
                    $this->dataForForm['applicant-' . $i . '-dob-date-year'] = $attorney->dob->date->format('Y');
                }

                if (++$i == self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
                    break;
                }
            }

            // Cross-out any unused boxes if we need less than 4
            if (count($this->lpa->document->whoIsRegistering) < 4) {
                $this->drawingTargets[19] = [];

                for ($x = 3; $x >= count($this->lpa->document->whoIsRegistering); $x--) {
                    $this->drawingTargets[19][] = "applicant-signature-{$x}";
                }
            }
        }

        //  Correspondent (Section 13)
        if ($this->lpa->document->correspondent instanceof Correspondence) {
            switch ($this->lpa->document->correspondent->who) {
                case Correspondence::WHO_DONOR:
                    $this->dataForForm['who-is-correspondent'] = 'donor';

                    if ($this->lpa->document->correspondent->contactDetailsEnteredManually === true) {
                        $this->dataForForm['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                        $this->dataForForm['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                        $this->dataForForm['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;
                        $this->dataForForm['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                        $this->dataForForm['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                        $this->dataForForm['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                        $this->dataForForm['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                    } else {
                        $this->drawingTargets[17] = ['correspondent-empty-name-address'];
                    }
                    break;
                case Correspondence::WHO_ATTORNEY:
                    $isAddressCrossedOut = true;

                    $this->dataForForm['who-is-correspondent'] = 'attorney';
                    if ($this->lpa->document->correspondent->name instanceof LongName) {
                        $this->dataForForm['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                        $this->dataForForm['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                        $this->dataForForm['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;

                        if ($this->lpa->document->correspondent->contactDetailsEnteredManually === true) {
                            $this->dataForForm['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                            $this->dataForForm['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                            $this->dataForForm['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                            $this->dataForForm['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                            $isAddressCrossedOut = false;
                        }
                    }

                    if ($isAddressCrossedOut) {
                        $this->drawingTargets[17] = ['correspondent-empty-address'];
                    }

                    $this->dataForForm['lpa-document-correspondent-company'] = $this->lpa->document->correspondent->company;

                    break;
                case Correspondence::WHO_CERTIFICATE_PROVIDER:
                case Correspondence::WHO_OTHER:
                    $this->dataForForm['who-is-correspondent'] = 'other';
                    $this->dataForForm['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                    $this->dataForForm['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                    $this->dataForForm['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;
                    $this->dataForForm['lpa-document-correspondent-company'] = $this->lpa->document->correspondent->company;

                    $this->dataForForm['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                    $this->dataForForm['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                    $this->dataForForm['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                    $this->dataForForm['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                    break;
            }

            // correspondence preference
            if ($this->lpa->document->correspondent->contactByPost === true) {
                $this->dataForForm['correspondent-contact-by-post'] = self::CHECK_BOX_ON;
            }

            if ($this->lpa->document->correspondent->phone instanceof PhoneNumber) {
                $this->dataForForm['correspondent-contact-by-phone'] = self::CHECK_BOX_ON;
                $this->dataForForm['lpa-document-correspondent-phone-number'] = str_replace(" ", "", $this->lpa->document->correspondent->phone->number);
            }

            if ($this->lpa->document->correspondent->email instanceof EmailAddress) {
                $this->dataForForm['correspondent-contact-by-email'] = self::CHECK_BOX_ON;
                $this->dataForForm['lpa-document-correspondent-email-address'] = $this->lpa->document->correspondent->email->address;
            }

            if ($this->lpa->document->correspondent->contactInWelsh === true) {
                $this->dataForForm['correspondent-contact-in-welsh'] = self::CHECK_BOX_ON;
            }
        }

        //  Payment section (section 14)
        //  Fee reduction, repeat application
        if ($this->lpa->repeatCaseNumber !== null) {
            $this->dataForForm['is-repeat-application'] = self::CHECK_BOX_ON;
            $this->dataForForm['repeat-application-case-number'] = $this->lpa->repeatCaseNumber;
        }

        if ($this->lpa->payment instanceof Payment) {
            // payment method
            if ($this->lpa->payment->method) {
                $this->dataForForm['pay-by'] = $this->lpa->payment->method;
            }

            if ($this->lpa->payment->method == Payment::PAYMENT_TYPE_CARD) {
                $this->dataForForm['lpa-payment-phone-number'] = "NOT REQUIRED.";
            }

            // apply to pay reduced fee
            if (($this->lpa->payment->reducedFeeReceivesBenefits && $this->lpa->payment->reducedFeeAwardedDamages)
                || $this->lpa->payment->reducedFeeLowIncome
                || $this->lpa->payment->reducedFeeUniversalCredit) {

                $this->dataForForm['apply-for-fee-reduction'] = self::CHECK_BOX_ON;
            }

            // Online payment details
            if ($this->lpa->payment->reference !== null) {
                $this->dataForForm['lpa-payment-reference'] = $this->lpa->payment->reference;
                $this->dataForForm['lpa-payment-amount'] = '£' . sprintf('%.2f', $this->lpa->payment->amount);
                $this->dataForForm['lpa-payment-date-day'] = $this->lpa->payment->date->format('d');
                $this->dataForForm['lpa-payment-date-month'] = $this->lpa->payment->date->format('m');
                $this->dataForForm['lpa-payment-date-year'] = $this->lpa->payment->date->format('Y');
            }
        }

        return $this->dataForForm;
    }

    /**
     * Merge generated intermediate pdf files
     */
    private function mergePdfs()
    {
        $pdf = new Pdf();
        $registrationPdf = new Pdf();

        $fileTag = $lp1FileTag = 'B';

        $pdf->addFile($this->interFileStack['Coversheet'], 'A');
        $pdf->addFile($this->interFileStack['LP1'], $lp1FileTag);

        //  Add the blank single page PDF incase we need to cat it around continuation sheets
        $pdf->addFile($this->getBlankPdfTemplateFilePath(), 'BLANK');

        $registrationPdf->addFile($this->interFileStack['Coversheet'], 'A');
        $registrationPdf->addFile($this->interFileStack['LP1'], $lp1FileTag);

        // Cover section
        // add cover sheet
        $pdf->cat(1, 'end', 'A');

        // Instrument section

        // add page 1-15
        $pdf->cat(1, 15, $lp1FileTag);

        // Section 11 - additional attorneys signature
        if (isset($this->interFileStack['AdditionalAttorneySignature'])) {
            foreach ($this->interFileStack['AdditionalAttorneySignature'] as $additionalAttorneySignature) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($additionalAttorneySignature, $fileTag);

                // add an additional attorney signature page
                $pdf->cat(1, null, $fileTag);
            }
        }

        // Continuation Sheet 1
        if (isset($this->interFileStack['CS1'])) {
            foreach ($this->interFileStack['CS1'] as $cs1) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($cs1, $fileTag);

                // add a CS1 page with a leading blank page
                $pdf->cat(1, null, 'BLANK');
                $pdf->cat(1, null, $fileTag);
            }
        }

        // Continuation Sheet 2
        if (isset($this->interFileStack['CS2'])) {
            foreach ($this->interFileStack['CS2'] as $cs2) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($cs2, $fileTag);

                // add a CS2 page with a leading blank page
                $pdf->cat(1, null, 'BLANK');
                $pdf->cat(1, null, $fileTag);
            }
        }

        // Continuation Sheet 3
        if (isset($this->interFileStack['CS3'])) {
            $fileTag = $this->nextTag($fileTag);
            $pdf->addFile($this->interFileStack['CS3'], $fileTag);

            // add a CS3 page with a leading blank page
            $pdf->cat(1, null, 'BLANK');
            $pdf->cat(1, null, $fileTag);
        }

        // Continuation Sheet 4
        if (isset($this->interFileStack['CS4'])) {
            $fileTag = $this->nextTag($fileTag);
            $pdf->addFile($this->interFileStack['CS4'], $fileTag);

            // add a CS4 page with a leading blank page
            $pdf->cat(1, null, 'BLANK');
            $pdf->cat(1, null, $fileTag);
        }

        //  If any continuation sheets were added then insert a trailing blank page
        if (array_key_exists('CS1', $this->interFileStack)
            || array_key_exists('CS2', $this->interFileStack)
            || array_key_exists('CS3', $this->interFileStack)
            || array_key_exists('CS4', $this->interFileStack)) {

            $pdf->cat(1, null, 'BLANK');
        }

        // Registration section

        // Use a different instance for the rest of the registration
        // pages so that (if needed) we can apply a stamp to them

        // Add the registration coversheet
        $registrationPdf->cat(16, null, $lp1FileTag);
        $registrationPdf->cat(17, null, $lp1FileTag);

        // Section 12 additional applicants
        if (isset($this->interFileStack['AdditionalApplicant'])) {
            foreach ($this->interFileStack['AdditionalApplicant'] as $additionalApplicant) {
                $fileTag = $this->nextTag($fileTag);
                $registrationPdf->addFile($additionalApplicant, $fileTag);

                // add an additional applicant page
                $registrationPdf->cat(1, null, $fileTag);
            }
        }

        // add page 18, 19, 20
        $registrationPdf->cat(18, 20, $lp1FileTag);

        // Section 15 - additional applicants signature
        if (isset($this->interFileStack['AdditionalApplicantSignature'])) {
            foreach ($this->interFileStack['AdditionalApplicantSignature'] as $additionalApplicantSignature) {
                $fileTag = $this->nextTag($fileTag);
                $registrationPdf->addFile($additionalApplicantSignature, $fileTag);

                // add an additional applicant signature page
                $registrationPdf->cat(1, null, $fileTag);
            }
        }

        //  If the registration section of the LPA isn't complete, we add the warning stamp
        if (!$this->registrationIsComplete) {
            $registrationPdf = new Pdf($registrationPdf);
            $registrationPdf->stamp($this->getPdfTemplateFilePath('RegistrationWatermark.pdf'));
        }

        // Merge the registration section in...
        $fileTag = $this->nextTag($fileTag);
        $pdf->addFile($registrationPdf, $fileTag);
        $pdf->cat(1, 'end', $fileTag);

        $this->generatedPdfFilePath = $this->getTmpFilePath();
        $pdf->saveAs($this->generatedPdfFilePath);
    }

    /**
     * Check if the text content can fit into the text box in the Section 7 page in the base PDF form.
     *
     * @return boolean
     */
    protected function canFitIntoTextBox($content)
    {
        $flattenContent = $this->flattenTextContent($content);
        return strlen($flattenContent) <= (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS;
    }
}
