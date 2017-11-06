<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use mikehaertl\pdftk\Pdf;
use RuntimeException;

class Lp3 extends AbstractTopForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LP3.pdf';

    /**
     * Variable to store LP3 PDF documents as they're generated
     *
     * @var array
     */
    private $lp3Pdfs = [];

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        $stateChecker = new StateChecker($lpa);

        //  Check that the document can be created
        if (!$stateChecker->canGenerateLP3()) {
            throw new RuntimeException('LPA does not contain all the required data to generate a LP3');
        }
    }

    /**
     * Get the LP3 arrays
     *
     * @return array
     */
    public function getLp3s()
    {
        return $this->lp3Pdfs;
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $formData = [];

        //  Loop through the people to notify and extract the PDF form data
        foreach ($this->lpa->document->peopleToNotify as $personToNotify) {
            if ($personToNotify instanceof NotifiedPerson) {
                //  If not already done, extract the data common to all copies of the LP3 document (donor and attorney details, etc)
                if (empty($formData)) {
                    $footerContent = $this->config['footer']['lp3'];

                    $formData['footer-right-page-one'] = $footerContent;

                    //  Page 2 data
                    $formData['lpa-document-donor-name-title'] = $this->lpa->document->donor->name->title;
                    $formData['lpa-document-donor-name-first'] = $this->lpa->document->donor->name->first;
                    $formData['lpa-document-donor-name-last'] = $this->lpa->document->donor->name->last;
                    $formData['lpa-document-donor-address-address1'] = $this->lpa->document->donor->address->address1;
                    $formData['lpa-document-donor-address-address2'] = $this->lpa->document->donor->address->address2;
                    $formData['lpa-document-donor-address-address3'] = $this->lpa->document->donor->address->address3;
                    $formData['lpa-document-donor-address-postcode'] = $this->lpa->document->donor->address->postcode;

                    if ($this->lpa->document->whoIsRegistering == 'donor') {
                        $formData['who-is-applicant'] = 'donor';
                    } else {
                        $formData['who-is-applicant'] = 'attorney';
                    }

                    //  If this is a PF LPA then the type text for the form data is slightly different
                    $formData['lpa-type'] = ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'property-and-financial-affairs' : $this->lpa->document->type);

                    $formData['footer-right-page-two'] = $footerContent;

                    //  Page 3 data
                    if (count($this->lpa->document->primaryAttorneys) == 1) {
                        $formData['how-attorneys-act'] = 'only-one-attorney-appointed';
                    } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                        $formData['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
                    }

                    $i = 0;
                    foreach ($this->lpa->document->primaryAttorneys as $attorney) {
                        if ($attorney instanceof TrustCorporation) {
                            $formData['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name;
                        } else {
                            $formData['lpa-document-primaryAttorneys-' . $i . '-name-title'] = $attorney->name->title;
                            $formData['lpa-document-primaryAttorneys-' . $i . '-name-first'] = $attorney->name->first;
                            $formData['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name->last;
                        }

                        $formData['lpa-document-primaryAttorneys-' . $i . '-address-address1'] = $attorney->address->address1;
                        $formData['lpa-document-primaryAttorneys-' . $i . '-address-address2'] = $attorney->address->address2;
                        $formData['lpa-document-primaryAttorneys-' . $i . '-address-address3'] = $attorney->address->address3;
                        $formData['lpa-document-primaryAttorneys-' . $i . '-address-postcode'] = $attorney->address->postcode;

                        if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                            break;
                        }
                    }

                    $formData['footer-right-page-three'] = $footerContent;

                    //  Page 4 data
                    $formData['footer-right-page-four'] = $footerContent;

                    //  Create a space for the individual people to notify details
                    $formData['ptn-data'] = [];
                }

                //  Extract the specific person to notify details - for page 1
                $formData['ptn-data'][] = [
                    'lpa-document-peopleToNotify-name-title' => $personToNotify->name->title,
                    'lpa-document-peopleToNotify-name-first' => $personToNotify->name->first,
                    'lpa-document-peopleToNotify-name-last' => $personToNotify->name->last,
                    'lpa-document-peopleToNotify-address-address1' => $personToNotify->address->address1,
                    'lpa-document-peopleToNotify-address-address2' => $personToNotify->address->address2,
                    'lpa-document-peopleToNotify-address-address3' => $personToNotify->address->address3,
                    'lpa-document-peopleToNotify-address-postcode' => $personToNotify->address->postcode,
                ];
            }
        }

        //  Determine if any cross lines should be drawn
        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);

        if ($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            for ($i = self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i >= 1; $i--) {
                $areaReference = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
                $this->addStrikeThrough($areaReference, 2);
            }
        }

        //  Loop through the PDF form data and generate the LP3 PDFs
        if (isset($formData['ptn-data']) && is_array($formData['ptn-data'])) {
            //  First get a copy of the common data
            $commonData = $formData;
            unset($commonData['ptn-data']);

            foreach ($formData['ptn-data'] as $ptnData) {
                $filePath = $this->registerTempFile('LP3');

                //  Get a combined copy of the data for this PDF and populate the form
                $fullFormData = array_merge($commonData, $ptnData);

                // populate forms
                $this->lp3Pdfs[] = $pdf = $this->getPdfObject(true);
                $pdf->fillForm($fullFormData)
                    ->flatten()
                    ->saveAs($filePath);

                $this->drawStrikeThroughs($filePath);
            }
        }

        if (count($this->lpa->document->primaryAttorneys) > self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            // depending on how many additional primary attorneys in the LPA, generate additional attorney pages.
            $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($this->lpa);
            $generatedAdditionalAttorneyPages = $lp3AdditionalAttorneyPage->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneyPages);
        }

        // merge intermediate files.
        $this->mergePdfs();

        $this->protectPdf();

        return $this;
    }

    /**
     * Merge intermediate pdf files into one file.
     */
    private function mergePdfs()
    {
        //  TODO - change this to fit into the new code to get the PDF from the AbstractForm function
        $pdf = new Pdf();

        $noOfLp3 = count($this->interFileStack['LP3']);
        $fileTag = 'A';

        for ($i = 0; $i < $noOfLp3; $i++) {
            $lp3Path = $this->interFileStack['LP3'][$i];
            $lp3FileTag = $fileTag;
            $pdf->addFile($lp3Path, $lp3FileTag);
            $blankPageRequired = false;

            //Concatenating the pdf pages forces the toolkit to compress the file significantly reducing its file size
            $pdf->cat(1, 3, $lp3FileTag);

            if (array_key_exists('AdditionalAttorneys', $this->interFileStack)) {
                foreach ($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
                    $fileTag = $this->nextTag($fileTag);
                    $pdf->addFile($additionalPage, $fileTag);
                    $pdf->cat(1, null, $fileTag);

                    //  Toggle the switch to add the blank page
                    $blankPageRequired = !$blankPageRequired;
                }
            }

            $pdf->cat(4, null, $lp3FileTag);
            $fileTag = $this->nextTag($fileTag);

            if ($blankPageRequired) {
                $pdf->addFile($this->getBlankPdfTemplateFilePath(), 'BLANK');
                $pdf->cat(1, null, 'BLANK');
            }
        }

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath();
        $pdf->saveAs($this->generatedPdfFilePath);
    }
}
