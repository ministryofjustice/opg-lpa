<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalAttorneySignaturePage extends AbstractForm
{
    /**
     * Duplicate Section 11 page for additional primary and replacement attorneys to sign
     *
     * @param Lpa $lpa
     */
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logGenerationStatement();

        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);

        $skipped=0;
        foreach($allAttorneys as $attorney) {

            // skip trust corp
            if($attorney instanceof TrustCorporation) continue;

            // skip first 4 human attorneys
            $skipped++;
            if($skipped <= Lp1::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) continue;

            $filePath = $this->registerTempFile('AdditionalAttorneySignature');

            $lpaType = ($this->lpa->document->type == Document::LPA_TYPE_PF)?'lp1f':'lp1h';

            $this->pdfFormData['signature-attorney-name-title'] = $attorney->name->title;
            $this->pdfFormData['signature-attorney-name-first'] = $attorney->name->first;
            $this->pdfFormData['signature-attorney-name-last'] = $attorney->name->last;
            $this->pdfFormData['footer-instrument-right-additional'] = Config::getInstance()['footer'][$lpaType]['instrument'];

            $this->pdf = new Pdf($this->pdfTemplatePath. (($this->lpa->document->type == Document::LPA_TYPE_PF)?"/LP1F_AdditionalAttorneySignature.pdf":"/LP1H_AdditionalAttorneySignature.pdf"));

            $this->pdf->fillForm($this->pdfFormData)
                      ->flatten()
                      ->saveAs($filePath);
        } //endforeach

        return $this->interFileStack;
    }
}
