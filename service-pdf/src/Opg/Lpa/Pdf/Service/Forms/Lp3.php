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
    private $Lp3Template, $Lp3PageOneTemplate, $Lp3PageTwoTemplate, $Lp3PageThreeTemplate, $Lp3PageFourTemplate;

    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP3');

        $this->Lp3Template = $this->pdfTemplatePath."/LP3.pdf";
        $this->Lp3PageOneTemplate = $this->pdfTemplatePath."/LP3-1.pdf";
        $this->Lp3PageTwoTemplate = $this->pdfTemplatePath."/LP3-2.pdf";
        $this->Lp3PageThreeTemplate = $this->pdfTemplatePath."/LP3-3.pdf";
        $this->Lp3PageFourTemplate = $this->pdfTemplatePath."/LP3-4.pdf";
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return Form object | null
     */
    public function generate()
    {
        $this->logGenerationStatement();

        // will not generate pdf if there's no people to notify
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify == 0) {
            throw new \RuntimeException("LP3 is not available for this LPA.");
        }

        if($noOfPeopleToNotify == 1) {
            $this->generateSingleNotificationPdf();
        }
        else {

            // Loop over each person, adding a page for them.
            foreach($this->lpa->document->peopleToNotify as $peopleToNotify) {

                if( $peopleToNotify instanceof NotifiedPerson ){
                    // Generate a standard notification letter for each person to be notified.
                    $this->generatePageOnePdf( $peopleToNotify );
                }

            }

            $this->generatePageTwoPdf();
            $this->generatePageThreePdf();
            $this->generatePageFourPdf();
        }

        // depending on how many additional primary attorneys in the LPA, generate additional attorney pages.
        $generatedAdditionalAttorneyPages = (new Lp3AdditionalAttorneyPage($this->lpa))->generate();
        $this->mergerIntermediateFilePaths($generatedAdditionalAttorneyPages);

        // merge intermediate files.
        $this->mergePdfs();

        $this->protectPdf();

        return $this;
    }

    /**
     * Fill LP3 form with values in the data model object.
     *
     * @param NotifiedPerson $peopleToNotify
     */
    protected function generateSingleNotificationPdf()
    {
        $pdf = PdftkInstance::getInstance($this->Lp3Template);

        $filePath = $this->registerTempFile('LP3');

        // populate forms
        $mappings = $this->dataMapping( current($this->lpa->document->peopleToNotify) );

        $pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($filePath);

        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $crossLineParams = array(2=>array());
            for($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i>=1; $i--) {
                // draw on page 2.
                $crossLineParams[2][] = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
            $this->drawCrossLines($filePath, $crossLineParams);
        }

    } // function generateStandardForm()

    protected function generatePageOnePdf(NotifiedPerson $peopleToNotify)
    {
        $pdf = PdftkInstance::getInstance($this->Lp3PageOneTemplate);

        $filePath = $this->registerTempFile('LP3-1');

        // populate forms
        $mappings = $this->dataMappingPageOne($peopleToNotify);

        $pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($filePath);
    }

    protected function generatePageTwoPdf()
    {
        $pdf = PdftkInstance::getInstance($this->Lp3PageTwoTemplate);

        $filePath = $this->registerTempFile('LP3-2');

        // populate forms
        $mappings = $this->dataMappingPageTwo();

        $pdf->fillForm($mappings)
        ->flatten()
        ->saveAs($filePath);
    }

    protected function generatePageThreePdf()
    {
        $pdf = PdftkInstance::getInstance($this->Lp3PageThreeTemplate);

        $filePath = $this->registerTempFile('LP3-3');

        // populate forms
        $mappings = $this->dataMappingPageThree();

        $pdf->fillForm($mappings)
        ->flatten()
        ->saveAs($filePath);
    }

    protected function generatePageFourPdf()
    {
        $pdf = PdftkInstance::getInstance($this->Lp3PageFourTemplate);

        $filePath = $this->registerTempFile('LP3-4');

        // populate forms
        $mappings = $this->dataMappingPageFour();

        $pdf->fillForm($mappings)
        ->flatten()
        ->saveAs($filePath);
    }

    /**
     * Data mapping
     * @param NotifiedPerson $peopleToNotify
     * @return array
     */
    protected function dataMapping(NotifiedPerson $peopleToNotify)
    {
        return array_merge($this->dataMappingPageOne($peopleToNotify), $this->dataMappingPageTwo(), $this->dataMappingPageThree(), $this->dataMappingPageFour());
    }

    /**
     * Data mapping
     * @param NotifiedPerson $peopleToNotify
     * @return array
     */
    protected function dataMappingPageOne(NotifiedPerson $peopleToNotify)
    {
        $pdfFormData = [];
        $pdfFormData['lpa-document-peopleToNotify-name-title']         = $peopleToNotify->name->title;
        $pdfFormData['lpa-document-peopleToNotify-name-first']         = $peopleToNotify->name->first;
        $pdfFormData['lpa-document-peopleToNotify-name-last']          = $peopleToNotify->name->last;
        $pdfFormData['lpa-document-peopleToNotify-address-address1']   = $peopleToNotify->address->address1;
        $pdfFormData['lpa-document-peopleToNotify-address-address2']   = $peopleToNotify->address->address2;
        $pdfFormData['lpa-document-peopleToNotify-address-address3']   = $peopleToNotify->address->address3;
        $pdfFormData['lpa-document-peopleToNotify-address-postcode']   = $peopleToNotify->address->postcode;

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

        if($this->lpa->document->whoIsRegistering == 'donor') {
            $pdfFormData['who-is-applicant'] = 'donor';
        }
        else {
            $pdfFormData['who-is-applicant'] = 'attorney';
        }

        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $pdfFormData['lpa-type'] = 'property-and-financial-affairs';
        }
        elseif($this->lpa->document->type == Document::LPA_TYPE_HW) {
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
        if(count($this->lpa->document->primaryAttorneys) == 1) {
            $pdfFormData['how-attorneys-act'] = 'only-one-attorney-appointed';
        } elseif ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $pdfFormData['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
        }

        $i=0;
        foreach($this->lpa->document->primaryAttorneys as $attorney) {
            if($attorney instanceof TrustCorporation) {
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name;
            }
            else {
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-title'] = $attorney->name->title;
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-first'] = $attorney->name->first;
                $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name->last;
            }

            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address1'] = $attorney->address->address1;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address2'] = $attorney->address->address2;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address3'] = $attorney->address->address3;
            $pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-postcode'] = $attorney->address->postcode;

            if(++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) break;
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
        if($this->countIntermediateFiles() == 1) {
            $this->generatedPdfFilePath = $this->interFileStack['LP3'][0];
            return;
        }

        $pdf = PdftkInstance::getInstance();

        $fileTag = 'A';
        if(array_key_exists('LP3', $this->interFileStack)) {
            $pdf->addFile($this->interFileStack['LP3'][0], 'A');
            if(array_key_exists('AdditionalAttorneys', $this->interFileStack)) {
                $pdf->cat(1, 3, $fileTag);
                foreach($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
                    $fileTag = $this->nextTag($fileTag);
                    $pdf->addFile($additionalPage, $fileTag);
                    $pdf->cat(1, null, $fileTag);
                }
                $pdf->cat(4, null, 'A');
            }
        }
        else {
            foreach($this->interFileStack['LP3-1'] as $lp3Path) {

                // attach page one
                $pdf->addFile($lp3Path, $fileTag);

                // add page one
                $pdf->cat(1, null, $fileTag);

                // attach page two
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($this->interFileStack['LP3-2'][0], $fileTag);

                // add page two
                $pdf->cat(1, null, $fileTag);

                // attach page three
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($this->interFileStack['LP3-3'][0], $fileTag);

                // add page three
                $pdf->cat(1, null, $fileTag);

                if(array_key_exists('AdditionalAttorneys', $this->interFileStack)) {
                    foreach($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
                        $fileTag = $this->nextTag($fileTag);
                        $pdf->addFile($additionalPage, $fileTag);
                        $pdf->cat(1, null, $fileTag);
                    }
                }

                // attach page four
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($this->interFileStack['LP3-4'][0], $fileTag);

                // add page four
                $pdf->cat(1, null, $fileTag);

                $fileTag = $this->nextTag($fileTag);

            } // endfor
        }

        $pdf->saveAs($this->generatedPdfFilePath);
    } // function mergePdfs()
}