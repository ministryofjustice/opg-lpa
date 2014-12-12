<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;

class Lp1f extends Lp1
{

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP1F-' . microtime(true) . '.pdf';
        
        $this->pdf = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath.'/LP1F.pdf');
    }

    protected function dataMappingForStandardForm ()
    {
        // make trust corp the first item in primaryAttorneys or replacementAttorneys
        $this->sortAttorneys();
        
        parent::dataMappingForStandardForm();
        
        // populate attorney dob
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        $idx = 0;
        for($i=0; $i<$noOfPrimaryAttorneys; $i++) {
            if($this->lpa->document->primaryAttorneys[$i] instanceof TrustCorporation) {
                $this->flattenLpa['attorney-0-is-trust-corporation'] = self::CHECK_BOX_ON;
                $this->flattenLpa['lpa-document-primaryAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-primaryAttorneys-0-name'];
                continue;
            }
            
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('Y');
            
            $idx++;
            
            if($idx==3) break;
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        $idx = 0;
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            if($this->lpa->document->replacementAttorneys[$i] instanceof TrustCorporation) {
                $this->flattenLpa['replacement-attorney-0-is-trust-corporation'] = 'On';
                $this->flattenLpa['lpa-document-replacementAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-replacementAttorneys-0-name'];
                continue;
            }
            
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-day'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-month'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-year'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('Y');
            
            $idx++;
            
            if($idx==1) break;
        }
        
        /**
         * When attroney can make decisions (Section 5)
         */
        if ($this->lpa->document->primaryAttorneyDecisions->when ==
                 Decisions\PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW) {
            $this->flattenLpa['attorneys-may-make-decisions-when-lpa-registered'] = 'On';
        } elseif ($this->lpa->document->primaryAttorneyDecisions->when ==
                 Decisions\PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY) {
            $this->flattenLpa['attorneys-may-make-decisions-when-donor-lost-mental-capacity'] = 'On';
        }
        
        /**
         *  Preference and Instructions. (Section 7)
         */
        if(empty($this->flattenLpa['lpa-document-preference'])) {
            $this->drawingTargets[7] = array('preference-pf');
        }
        
        if(empty($this->flattenLpa['lpa-document-instruction'])) {
            $this->drawingTargets[7] = isset($this->drawingTargets[7])? array('preference-pf', 'instruction-pf'):array('instruction-pf');
        }
        
        // if number of attorneys (including replacements) is greater than 4, duplicate 
        // Section 11 - Attorneys Signatures as many as needed to be able to fit all attorneys in the form.
        $totalAttorneys = count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys);
        if($this->hasTrustCorporation()) {
            $totalHumanAttorneys = $totalAttorneys - 1;
        }
        else {
            $totalHumanAttorneys = $totalAttorneys;
        }
        
        if( $totalHumanAttorneys > 4 ) {
            $generatedAdditionalAttorneySignaturePages = (new Lp1AdditionalAttorneySignaturePage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneySignaturePages);
        }
        
        return $this->flattenLpa;
    }

    protected function generateAdditionalPages ()
    {
        parent::generateAdditionalPages();
        
        // CS4
        if ($this->hasTrustCorporation()) {
            $generatedCs4 = (new Cs4($this->lpa, $this->getTrustCorporation()->number))->generate();
            $this->mergerIntermediateFilePaths($generatedCs4);
        }
    }

    /**
     * check if there is a trust corp in the whole LPA or in primary attorneys or replacement attorneys.
     */
    protected function hasTrustCorporation ($attorneys=null)
    {
        if(null == $attorneys) {
            foreach($this->lpa->document->primaryAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return true;
                }
            }
            
            foreach($this->lpa->document->replacementAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return true;
                }
            }
        }
        else {
            foreach($attorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return true;
                }
            }
        }
        
        return false;
    } // function hasTrustCorporation()
    
    /**
     * get trust corporation object from lpa object or from primary attorneys or replacement attorneys array.
     * 
     * @param string $attorneys
     * @return \Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation|NULL
     */
    public function getTrustCorporation($attorneys=null)
    {
        if(null == $attorneys) {
            foreach($this->lpa->document->primaryAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
            
            foreach($this->lpa->document->replacementAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
        }
        else {
            foreach($attorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
        }
        
        return null;
    }
    
    /**
     * if there is a trust corp, make it the first item in the attorneys array.
     */
    protected function sortAttorneys()
    {
        if($this->hasTrustCorporation($this->lpa->document->primaryAttorneys)) {
            $attorneys = $this->lpa->document->primaryAttorneys;
        }
        elseif($this->hasTrustCorporation($this->lpa->document->replacementAttorneys)) {
            $attorneys = $this->lpa->document->replacementAttorneys;
        }
        
        if(isset($attorneys)) {
            foreach($attorneys as $idx=>$attorney) {
                $trustCorp = $attorney;
                break;
            }
            unset($attorneys[$idx]);
            array_unshift($attorneys, $trustCorp);
        }
    }
} // class