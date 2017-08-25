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

        //  Loop through the people to notify and extract the PDF form data
        foreach ($this->lpa->document->peopleToNotify as $personToNotify) {
            if ($personToNotify instanceof NotifiedPerson) {
                $this->dataMapping($personToNotify);
            }
        }

        //  Determine if any cross lines should be drawn
        $crossLineParams = [];
        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);

        if ($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $crossLineParams[2] = [];

            for ($i = self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i >= 1; $i--) {
                // draw on page 2.
                $crossLineParams[2][] = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
        }

        //  Loop through the PDF form data and generate the LP3 PDFs
        if (isset($this->dataForForm['ptn-data']) && is_array($this->dataForForm['ptn-data'])) {
            //  First get a copy of the common data
            $commonData = $this->dataForForm;
            unset($commonData['ptn-data']);

            foreach ($this->dataForForm['ptn-data'] as $ptnData) {
                $filePath = $this->registerTempFile('LP3');

                //  Get a combined copy of the data for this PDF and populate the form
                $formData = array_merge($commonData, $ptnData);

                // populate forms
                $this->lp3Pdfs[] = $pdf = $this->getPdfObject(true);
                $pdf->fillForm($formData)
                    ->flatten()
                    ->saveAs($filePath);

                if (!empty($crossLineParams)) {
                    $this->drawCrossLines($filePath, $crossLineParams);
                }
            }
        }

        if (count($this->lpa->document->primaryAttorneys) > self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            // depending on how many additional primary attorneys in the LPA, generate additional attorney pages.
            $generatedAdditionalAttorneyPages = (new Lp3AdditionalAttorneyPage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneyPages);
        }

        // merge intermediate files.
        $this->mergePdfs();

        $this->protectPdf();

        return $this;
    }

    /**
     * Data mapping
     * @param NotifiedPerson $personToNotify
     * @return array
     */
    protected function dataMapping(NotifiedPerson $personToNotify)
    {
        //  If not already done, extract the data common to all copies of the LP3 document (donor and attorney details, etc)
        if (empty($this->dataForForm)) {
            $this->dataForForm['footer-right-page-one'] = $this->config['footer']['lp3'];

            //  Page 2 data
            $this->dataForForm['lpa-document-donor-name-title'] = $this->lpa->document->donor->name->title;
            $this->dataForForm['lpa-document-donor-name-first'] = $this->lpa->document->donor->name->first;
            $this->dataForForm['lpa-document-donor-name-last'] = $this->lpa->document->donor->name->last;
            $this->dataForForm['lpa-document-donor-address-address1'] = $this->lpa->document->donor->address->address1;
            $this->dataForForm['lpa-document-donor-address-address2'] = $this->lpa->document->donor->address->address2;
            $this->dataForForm['lpa-document-donor-address-address3'] = $this->lpa->document->donor->address->address3;
            $this->dataForForm['lpa-document-donor-address-postcode'] = $this->lpa->document->donor->address->postcode;

            if ($this->lpa->document->whoIsRegistering == 'donor') {
                $this->dataForForm['who-is-applicant'] = 'donor';
            } else {
                $this->dataForForm['who-is-applicant'] = 'attorney';
            }

            if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->dataForForm['lpa-type'] = 'property-and-financial-affairs';
            } elseif ($this->lpa->document->type == Document::LPA_TYPE_HW) {
                $this->dataForForm['lpa-type'] = 'health-and-welfare';
            }

            $this->dataForForm['footer-right-page-two'] = $this->config['footer']['lp3'];

            //  Page 3 data
            if (count($this->lpa->document->primaryAttorneys) == 1) {
                $this->dataForForm['how-attorneys-act'] = 'only-one-attorney-appointed';
            } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $this->dataForForm['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
            }

            $i = 0;
            foreach ($this->lpa->document->primaryAttorneys as $attorney) {
                if ($attorney instanceof TrustCorporation) {
                    $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name;
                } else {
                    $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-title'] = $attorney->name->title;
                    $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-first'] = $attorney->name->first;
                    $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name->last;
                }

                $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address1'] = $attorney->address->address1;
                $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address2'] = $attorney->address->address2;
                $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address3'] = $attorney->address->address3;
                $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-postcode'] = $attorney->address->postcode;

                if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                    break;
                }
            }

            $this->dataForForm['footer-right-page-three'] = $this->config['footer']['lp3'];

            //  Page 4 data
            $this->dataForForm['footer-right-page-four'] = $this->config['footer']['lp3'];

            //  Create a space for the individual people to notify details
            $this->dataForForm['ptn-data'] = [];
        }

        //  Extract the specific person to notify details - for page 1
        $this->dataForForm['ptn-data'][] = [
            'lpa-document-peopleToNotify-name-title' => $personToNotify->name->title,
            'lpa-document-peopleToNotify-name-first' => $personToNotify->name->first,
            'lpa-document-peopleToNotify-name-last' => $personToNotify->name->last,
            'lpa-document-peopleToNotify-address-address1' => $personToNotify->address->address1,
            'lpa-document-peopleToNotify-address-address2' => $personToNotify->address->address2,
            'lpa-document-peopleToNotify-address-address3' => $personToNotify->address->address3,
            'lpa-document-peopleToNotify-address-postcode' => $personToNotify->address->postcode,
        ];

        return $this->dataForForm;
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
