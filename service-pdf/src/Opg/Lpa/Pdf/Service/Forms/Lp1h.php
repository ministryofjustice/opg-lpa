<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;

class Lp1h extends Lp1
{

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP1F-' . microtime(true) . '.pdf';
        
        $this->pdf = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath.'/LP1H.pdf');
    }
    
    public function dataMappingForStandardForm()
    {
        parent::dataMappingForStandardForm();
        if($this->flattenLpa['lpa-document-primaryAttorneyDecisions-canSustainLife'] === true) {
            $this->drawingTargets[5] = array('life-sustain-B');
        }
        else {
            $this->drawingTargets[5] = array('life-sustain-A');
        }
        
        return $this->flattenLpa;
    }
} // class