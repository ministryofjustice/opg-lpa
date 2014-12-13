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
    
    public function dataMapping()
    {
        parent::dataMapping();
        
        // Section 2
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        for($i=0; $i<$noOfReplacementAttorneys; $i++) {
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('Y');
            if($i==3) break;
        }
        
        // Section 4
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('Y');
            if($i==1) break;
        }
        
        // section 5
        if($this->flattenLpa['lpa-document-primaryAttorneyDecisions-canSustainLife'] === true) {
            $this->drawingTargets[5] = array('life-sustain-B');
        }
        else {
            $this->drawingTargets[5] = array('life-sustain-A');
        }

        /**
         *  Preference and Instructions. (Section 7)
         */
        if(empty($this->flattenLpa['lpa-document-preference'])) {
            $this->drawingTargets[7] = array('preference-hw');
        }
        
        if(empty($this->flattenLpa['lpa-document-instruction'])) {
            $this->drawingTargets[7] = isset($this->drawingTargets[7])? array('preference-hw', 'instruction-hw'):array('instruction-hw');
        }
        
        // if number of attorneys (including replacements) is greater than 4, duplicate 
        // Section 11 - Attorneys Signatures as many as needed to be able to fit all attorneys in the form.
        $totalAttorneys = count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys);
        if( $totalAttorneys > 4 ) {
            $generatedAdditionalAttorneySignaturePages = (new Lp1AdditionalAttorneySignaturePage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneySignaturePages);
        }
        
        return $this->flattenLpa;
    } // function dataMapping()
} // class