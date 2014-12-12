<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;

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
            if($skipped <= self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) continue;
            
            $filePath = $this->registerTempFile('AdditionalAttorneySignature');
            
            $attorneySignaturePage = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LP1_AdditionalAttorneySignature.pdf");
            $attorneySignaturePage->fillForm(array(
                    'signature-attorney-name-title' => $attorney->name->title,
                    'signature-attorney-name-first' => $attorney->name->first,
                    'signature-attorney-name-last'  => $attorney->name->last
            ))->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($attorneySignaturePage);
            
        } //endforeach
        
        return $this->intermediateFilePaths;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class AdditionalAttorneySignaturePage