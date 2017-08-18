<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Lp3 extends AbstractForm
{
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;

    /**
     * Variable to store LP3 PDF documents as they're generated
     *
     * @var array
     */
    public $lp3Pdfs = [];

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP3');
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

        // will not generate pdf if there's no people to notify
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if ($noOfPeopleToNotify == 0) {
            throw new \RuntimeException("LP3 is not available for this LPA.");
        }

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
        if (isset($this->pdfFormData['ptn-data']) && is_array($this->pdfFormData['ptn-data'])) {
            //  First get a copy of the common data
            $commonData = $this->pdfFormData;
            unset($commonData['ptn-data']);

            foreach ($this->pdfFormData['ptn-data'] as $ptnData) {
                $filePath = $this->registerTempFile('LP3');

                //  Get a combined copy of the data for this PDF and populate the form
                $formData = array_merge($commonData, $ptnData);

                // populate forms
                $this->lp3Pdfs[] = $pdf = new Pdf($this->pdfTemplatePath . '/LP3.pdf');
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
        if (empty($this->pdfFormData)) {
            $this->pdfFormData['footer-right-page-one'] = Config::getInstance()['footer']['lp3'];

            //  Page 2 data
            $this->pdfFormData['lpa-document-donor-name-title'] = $this->lpa->document->donor->name->title;
            $this->pdfFormData['lpa-document-donor-name-first'] = $this->lpa->document->donor->name->first;
            $this->pdfFormData['lpa-document-donor-name-last'] = $this->lpa->document->donor->name->last;
            $this->pdfFormData['lpa-document-donor-address-address1'] = $this->lpa->document->donor->address->address1;
            $this->pdfFormData['lpa-document-donor-address-address2'] = $this->lpa->document->donor->address->address2;
            $this->pdfFormData['lpa-document-donor-address-address3'] = $this->lpa->document->donor->address->address3;
            $this->pdfFormData['lpa-document-donor-address-postcode'] = $this->lpa->document->donor->address->postcode;

            if ($this->lpa->document->whoIsRegistering == 'donor') {
                $this->pdfFormData['who-is-applicant'] = 'donor';
            } else {
                $this->pdfFormData['who-is-applicant'] = 'attorney';
            }

            if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->pdfFormData['lpa-type'] = 'property-and-financial-affairs';
            } elseif ($this->lpa->document->type == Document::LPA_TYPE_HW) {
                $this->pdfFormData['lpa-type'] = 'health-and-welfare';
            }

            $this->pdfFormData['footer-right-page-two'] = Config::getInstance()['footer']['lp3'];

            //  Page 3 data
            if (count($this->lpa->document->primaryAttorneys) == 1) {
                $this->pdfFormData['how-attorneys-act'] = 'only-one-attorney-appointed';
            } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $this->pdfFormData['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
            }

            $i = 0;
            foreach ($this->lpa->document->primaryAttorneys as $attorney) {
                if ($attorney instanceof TrustCorporation) {
                    $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name;
                } else {
                    $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-name-title'] = $attorney->name->title;
                    $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-name-first'] = $attorney->name->first;
                    $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $attorney->name->last;
                }

                $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-address-address1'] = $attorney->address->address1;
                $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-address-address2'] = $attorney->address->address2;
                $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-address-address3'] = $attorney->address->address3;
                $this->pdfFormData['lpa-document-primaryAttorneys-' . $i . '-address-postcode'] = $attorney->address->postcode;

                if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                    break;
                }
            }

            $this->pdfFormData['footer-right-page-three'] = Config::getInstance()['footer']['lp3'];

            //  Page 4 data
            $this->pdfFormData['footer-right-page-four'] = Config::getInstance()['footer']['lp3'];

            //  Create a space for the individual people to notify details
            $this->pdfFormData['ptn-data'] = [];
        }

        //  Extract the specific person to notify details - for page 1
        $this->pdfFormData['ptn-data'][] = [
            'lpa-document-peopleToNotify-name-title' => $personToNotify->name->title,
            'lpa-document-peopleToNotify-name-first' => $personToNotify->name->first,
            'lpa-document-peopleToNotify-name-last' => $personToNotify->name->last,
            'lpa-document-peopleToNotify-address-address1' => $personToNotify->address->address1,
            'lpa-document-peopleToNotify-address-address2' => $personToNotify->address->address2,
            'lpa-document-peopleToNotify-address-address3' => $personToNotify->address->address3,
            'lpa-document-peopleToNotify-address-postcode' => $personToNotify->address->postcode,
        ];

        return $this->pdfFormData;
    }

    /**
     * Merge intermediate pdf files into one file.
     */
    protected function mergePdfs()
    {
        $this->pdf = new Pdf();

        $noOfLp3 = count($this->interFileStack['LP3']);
        $fileTag = 'A';

        for ($i = 0; $i < $noOfLp3; $i++) {
            $lp3Path = $this->interFileStack['LP3'][$i];
            $lp3FileTag = $fileTag;
            $this->pdf->addFile($lp3Path, $lp3FileTag);
            $blankPageRequired = false;

            //Concatenating the pdf pages forces the toolkit to compress the file significantly reducing its file size
            $this->pdf->cat(1, 3, $lp3FileTag);

            if (array_key_exists('AdditionalAttorneys', $this->interFileStack)) {
                foreach ($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
                    $fileTag = $this->nextTag($fileTag);
                    $this->pdf->addFile($additionalPage, $fileTag);
                    $this->pdf->cat(1, null, $fileTag);

                    //  Toggle the switch to add the blank page
                    $blankPageRequired = !$blankPageRequired;
                }
            }

            $this->pdf->cat(4, null, $lp3FileTag);
            $fileTag = $this->nextTag($fileTag);

            if ($blankPageRequired) {
                $fileName = $this->pdfTemplatePath . '/blank.pdf';
                $this->pdf->addFile($fileName, 'BLANK');
                $this->pdf->cat(1, null, 'BLANK');
            }
        }

        $this->pdf->saveAs($this->generatedPdfFilePath);
    }
}
