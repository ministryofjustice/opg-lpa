<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Lp1AdditionalApplicantPage extends AbstractForm
{
    /**
     * Duplicate Section 12 page for additional applicants
     *
     * @param Lpa $lpa
     */
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logger->info('Generating Lpa Additional Applicant Page', [
            'lpaId' => $this->lpa->id
        ]);

        $totalApplicant = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicant = $totalApplicant - Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM;
        $totalAdditionalPages = ceil($totalAdditionalApplicant/Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM);

        $totalMappedAdditionalApplicants = 0;
        for($i=0; $i<$totalAdditionalPages; $i++) {

            $filePath = $this->registerTempFile('AdditionalApplicant');

            $additionalApplicant = PdftkInstance::getInstance($this->pdfTemplatePath. (($this->lpa->document->type == Document::LPA_TYPE_PF)?"/LP1F_AdditionalApplicant.pdf":"/LP1H_AdditionalApplicant.pdf"));

            $formData = array();
            for($j=0; $j<Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $j++) {

                $attorneyId = $this->lpa->document->whoIsRegistering[(1+$i)*Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM + $j];
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);

                if($attorney instanceof TrustCorporation) {
                    $formData['applicant-'.$j.'-name-last']      = $attorney->name;
                }
                else {
                    $formData['applicant-'.$j.'-name-title']     = $attorney->name->title;
                    $formData['applicant-'.$j.'-name-first']     = $attorney->name->first;
                    $formData['applicant-'.$j.'-name-last']      = $attorney->name->last;
                    $formData['applicant-'.$j.'-dob-date-day']   = $attorney->dob->date->format('d');
                    $formData['applicant-'.$j.'-dob-date-month'] = $attorney->dob->date->format('m');
                    $formData['applicant-'.$j.'-dob-date-year']  = $attorney->dob->date->format('Y');
                }

                if(++$totalMappedAdditionalApplicants >= $totalAdditionalApplicant) {
                    break;
                }
            } // endfor

            $formData['who-is-applicant'] = 'attorney';

            if($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $formData['footer-registration-right-additional'] = Config::getInstance()['footer']['lp1f']['registration'];
            }
            else {
                $formData['footer-registration-right-additional'] = Config::getInstance()['footer']['lp1h']['registration'];
            }

            $additionalApplicant->fillForm($formData)
                ->flatten()
                ->saveAs($filePath);

        } // endfor

        // draw cross lines if there's any blank slot
        if($totalAdditionalApplicant % Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
            $crossLineParams = array(array());
            for($i=Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $totalAdditionalApplicant % Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $i>=1; $i--) {
                $crossLineParams[0][] = 'additional-applicant-'.(Lp1::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $i) . '-' . (($this->lpa->document->type == Document::LPA_TYPE_PF)?'pf':'hw');
            }
            $this->drawCrossLines($filePath, $crossLineParams);
        }

        return $this->interFileStack;
    }

    public function __destruct()
    {
    }
}