<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class Lp1AdditionalAttorneySignaturePage extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  [
        Document::LPA_TYPE_PF => 'LP1F_AdditionalAttorneySignature.pdf',
        Document::LPA_TYPE_HW => 'LP1H_AdditionalAttorneySignature.pdf',
    ];

    public function generate()
    {
        $this->logGenerationStatement();

        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);

        $skipped = 0;

        foreach ($allAttorneys as $attorney) {
            // skip trust corp
            if ($attorney instanceof TrustCorporation) {
                continue;
            }

            // skip first 4 human attorneys
            $skipped++;
            if ($skipped <= self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) {
                continue;
            }

            $filePath = $this->registerTempFile('AdditionalAttorneySignature');

            $lpaType = ($this->lpa->document->type == Document::LPA_TYPE_PF)?'lp1f':'lp1h';

            $this->dataForForm['signature-attorney-name-title'] = $attorney->name->title;
            $this->dataForForm['signature-attorney-name-first'] = $attorney->name->first;
            $this->dataForForm['signature-attorney-name-last'] = $attorney->name->last;
            $this->dataForForm['footer-instrument-right-additional'] = $this->config['footer'][$lpaType]['instrument'];

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($this->dataForForm)
                ->flatten()
                ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
