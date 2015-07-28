<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use mikehaertl\pdftk\Pdf as PdftkInstance;

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
            $attorneySignaturePage = new PdftkInstance($this->pdfTemplatePath. (($this->lpa->document->type == Document::LPA_TYPE_PF)?"/LP1F_AdditionalAttorneySignature.pdf":"/LP1H_AdditionalAttorneySignature.pdf"));
            $attorneySignaturePage->fillForm(array(
                    'signature-attorney-name-title' => $attorney->name->title,
                    'signature-attorney-name-first' => $attorney->name->first,
                    'signature-attorney-name-last'  => $attorney->name->last,
                    'footer_instrument_right-pf'    => ($this->lpa->document->type == Document::LPA_TYPE_PF)?Config::getInstance()['footer'][$lpaType]['instrument']:null,
                    'footer_instrument_right-hw'    => ($this->lpa->document->type == Document::LPA_TYPE_HW)?Config::getInstance()['footer'][$lpaType]['instrument']:null,
            ))
            ->flatten()
            ->saveAs($filePath);
            
        } //endforeach
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Lp1AdditionalAttorneySignaturePage