<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Lp3 extends AbstractForm
{
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP3');

        $this->pdf = PdftkInstance::getInstance($this->pdfTemplatePath.'/LP3.pdf');
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

        foreach ($this->lpa->document->peopleToNotify as $personToNotify) {
            if ($personToNotify instanceof NotifiedPerson) {
                // Generate a standard notification letter for each person to be notified.
                $this->generateNotificationPdf($personToNotify);
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
     * Fill LP3 form with values in the data model object.
     *
     * @param NotifiedPerson $personToNotify
     */
    protected function generateNotificationPdf(NotifiedPerson $personToNotify)
    {
        $filePath = $this->registerTempFile('LP3');

        // populate forms
        $mappings = $this->dataMapping($personToNotify);

        $this->pdf->fillForm($mappings)
             ->flatten()
             ->saveAs($filePath);

        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if ($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $crossLineParams = array(2=>array());
            for ($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i>=1; $i--) {
                // draw on page 2.
                $crossLineParams[2][] = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
            $this->drawCrossLines($filePath, $crossLineParams);
        }
    } // function generateStandardForm()

    /**
     * Data mapping
     * @param NotifiedPerson $personToNotify
     * @return array
     */
    protected function dataMapping(NotifiedPerson $personToNotify)
    {
        return array_merge(
            $this->dataMappingPageOne($personToNotify),
            $this->dataMappingPageTwo(),
            $this->dataMappingPageThree(),
            $this->dataMappingPageFour()
        );
    }

    /**
     * Data mapping
     * @param NotifiedPerson $personToNotify
     * @return array
     */
    protected function dataMappingPageOne(NotifiedPerson $personToNotify)
    {
        $pdfFormData = [];
        $pdfFormData['lpa-document-peopleToNotify-name-title']         = $personToNotify->name->title;
        $pdfFormData['lpa-document-peopleToNotify-name-first']         = $personToNotify->name->first;
        $pdfFormData['lpa-document-peopleToNotify-name-last']          = $personToNotify->name->last;
        $pdfFormData['lpa-document-peopleToNotify-address-address1']   = $personToNotify->address->address1;
        $pdfFormData['lpa-document-peopleToNotify-address-address2']   = $personToNotify->address->address2;
        $pdfFormData['lpa-document-peopleToNotify-address-address3']   = $personToNotify->address->address3;
        $pdfFormData['lpa-document-peopleToNotify-address-postcode']   = $personToNotify->address->postcode;

        $pdfFormData['footer-right-page-one'] = Config::getInstance()['footer']['lp3'];

        return $pdfFormData;
    } // function dataMapping()

    /**
     * Data mapping
     *
     * @return array
     */
    protected function dataMappingPageTwo()
    {
        $pdfFormData = [];
        $pdfFormData['lpa-document-donor-name-title']         = $this->lpa->document->donor->name->title;
        $pdfFormData['lpa-document-donor-name-first']         = $this->lpa->document->donor->name->first;
        $pdfFormData['lpa-document-donor-name-last']          = $this->lpa->document->donor->name->last;
        $pdfFormData['lpa-document-donor-address-address1']   = $this->lpa->document->donor->address->address1;
        $pdfFormData['lpa-document-donor-address-address2']   = $this->lpa->document->donor->address->address2;
        $pdfFormData['lpa-document-donor-address-address3']   = $this->lpa->document->donor->address->address3;
        $pdfFormData['lpa-document-donor-address-postcode']   = $this->lpa->document->donor->address->postcode;

        if ($this->lpa->document->whoIsRegistering == 'donor') {
            $pdfFormData['who-is-applicant'] = 'donor';
        } else {
            $pdfFormData['who-is-applicant'] = 'attorney';
        }

        if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $pdfFormData['lpa-type'] = 'property-and-financial-affairs';
        } elseif ($this->lpa->document->type == Document::LPA_TYPE_HW) {
            $pdfFormData['lpa-type'] = 'health-and-welfare';
        }

        $pdfFormData['footer-right-page-two'] = Config::getInstance()['footer']['lp3'];

        return $pdfFormData;
    } // function dataMapping()

    /**
     * Data mapping
     *
     * @return array
     */
    protected function dataMappingPageThree()
    {
        $pdfFormData = [];
        if (count($this->lpa->document->primaryAttorneys) == 1) {
            $pdfFormData['how-attorneys-act'] = 'only-one-attorney-appointed';
        } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $pdfFormData['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
        }

        $i=0;
        foreach ($this->lpa->document->primaryAttorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name;
            } else {
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-title'] = $attorney->name->title;
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-first'] = $attorney->name->first;
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name->last;
            }

            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address1'] = $attorney->address->address1;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address2'] = $attorney->address->address2;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address3'] = $attorney->address->address3;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-postcode'] = $attorney->address->postcode;

            if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                break;
            }
        }

        $pdfFormData['footer-right-page-three'] = Config::getInstance()['footer']['lp3'];

        return $pdfFormData;
    } // function dataMapping()

    /**
     * Data mapping
     *
     * @return array
     */
    protected function dataMappingPageFour()
    {
        $pdfFormData = [];

        $pdfFormData['footer-right-page-four'] = Config::getInstance()['footer']['lp3'];

        return $pdfFormData;
    } // function dataMapping()

    /**
     * Merge intermediate pdf files into one file.
     */
    protected function mergePdfs()
    {
        $pdf = PdftkInstance::getInstance();

        $noOfLp3 = count($this->interFileStack['LP3']);
        $fileTag = 'A';
        for ($i = 0; $i < $noOfLp3; $i++) {
            $lp3Path = $this->interFileStack['LP3'][$i];
            $lp3FileTag = $fileTag;
            $pdf->addFile($lp3Path, $lp3FileTag);
            //Concatenating the pdf pages forces the toolkit to compress the file significantly reducing its file size
            $pdf->cat(1, 3, $lp3FileTag);
            if (array_key_exists('AdditionalAttorneys', $this->interFileStack)) {
                foreach ($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
                    $fileTag = $this->nextTag($fileTag);
                    $pdf->addFile($additionalPage, $fileTag);
                    $pdf->cat(1, null, $fileTag);
                }
            }
            $pdf->cat(4, null, $lp3FileTag);
            $fileTag = $this->nextTag($fileTag);

            //If the number of attorney pages is an even number, we need to add a blank page
            //to ensure double sided printing works correctly
            $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
            if ($i + 1 < $noOfLp3 && floor($numOfAttorneys/self::MAX_ATTORNEYS_ON_STANDARD_FORM)%2 == 1) {
                $fileName = Config::getInstance()['service']['assets']['source_template_path'] . '/blank.pdf';
                $pdf->addFile($fileName, 'BLANK');
                $pdf->cat(1, null, 'BLANK');
            }
        }

        $pdf->saveAs($this->generatedPdfFilePath);
    } // function mergePdfs()
}
