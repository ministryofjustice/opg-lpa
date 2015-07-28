<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\Pdf as PdftkInstance;

class CoversheetInstrument extends AbstractForm
{
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }
    
    public function generate()
    {
        $filePath = $this->registerTempFile('Coversheet');
        
        $coversheetInstrument = new PdftkInstance($this->pdfTemplatePath.'/LP1_CoversheetInstrument.pdf');
        
        $coversheetInstrument->fillForm(
            array(
                    'lpa-type'   => ("property-and-financial" == $this->lpa->document->type)? 'property and financial affairs.':'health and welfare.',
                    'lpa-number' => \Opg\Lpa\DataModel\Lpa\Formatter::id($this->lpa->id).'.',
            ))
        ->flatten()
        ->saveAs($filePath);
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class CoversheetInstrument