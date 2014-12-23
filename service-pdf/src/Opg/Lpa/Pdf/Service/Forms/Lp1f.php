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

    protected function dataMapping()
    {
        parent::dataMapping();
        
        // populate attorney dob
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        
        // make trust corp the first item in primaryAttorneys or replacementAttorneys
        $primaryAttorneys = $this->getSortedAttorneys('primaryAttorneys');
        $idx = 0;
        for($i=0; $i<$noOfPrimaryAttorneys; $i++) {
            if($primaryAttorneys[$i] instanceof TrustCorporation) {
                $this->flattenLpa['attorney-0-is-trust-corporation'] = self::CHECK_BOX_ON;
                $this->flattenLpa['lpa-document-primaryAttorneys-0-name-last'] = $primaryAttorneys[$i]->name;
                continue;
            }
            
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-day'] = $primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-month'] = $primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$idx.'-dob-date-year'] = $primaryAttorneys[$i]->dob->date->format('Y');
            
            $idx++;
            
            if($idx==3) break;
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        $replacementAttorneys = $this->getSortedAttorneys('replacementAttorneys');
        $idx = 0;
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            if($this->lpa->document->replacementAttorneys[$i] instanceof TrustCorporation) {
                $this->flattenLpa['replacement-attorney-0-is-trust-corporation'] = self::CHECK_BOX_ON;
                $this->flattenLpa['lpa-document-replacementAttorneys-0-name-last'] = $replacementAttorneys[$i]->name;
                continue;
            }
            
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-day'] = $replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-month'] = $replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$idx.'-dob-date-year'] = $replacementAttorneys[$i]->dob->date->format('Y');
            
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
    } // function dataMapping();

    protected function generateAdditionalPages ()
    {
        parent::generateAdditionalPages();
        
        // CS1 is generated when number of attorneys that are larger than what is available on standard form. 
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys > 4) {
            $generatedCs1 = (new Cs1($this->lpa, 'primaryAttorney', $this->getSortedAttorneys('primaryAttorneys')))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $generatedCs1 = (new Cs1($this->lpa, 'replacementAttorney', $this->getSortedAttorneys('replacementAttorneys')))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
                
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
     * 
     * @param string $attorneyGroup - 'primaryAttorneys'|'replacementAttorneys'
     * @return array of primaryAttorneys or replacementAttorneys
     */
    protected function getSortedAttorneys($attorneyGroup)
    {
        if(empty($this->lpa->document->$attorneyGroup)) {
            return $this->lpa->document->$attorneyGroup;
        }
        
        if($this->hasTrustCorporation($this->lpa->document->$attorneyGroup)) {
            $attorneys = $this->lpa->document->$attorneyGroup;
        }
        else {
            return $this->lpa->document->$attorneyGroup;
        }
        
        $sortedAttorneys = [];
        foreach($attorneys as $idx=>$attorney) {
            if($attorney instanceof TrustCorporation) {
                $trustCorp = $attorney;
            }
            else {
                $sortedAttorneys[] = $attorney;
            }
        }
        
        array_unshift($sortedAttorneys, $trustCorp);
        return $sortedAttorneys;
    }
} // class