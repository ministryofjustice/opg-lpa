<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;
use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;

class Lp1f extends Lp1
{

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedpPdfFilePath = 'pdf-'.Formatter::id($this->lpa->id).'-LP1F-'.time().'.pdf';
        
        $this->pdf = new Pdf("../assets/v2/LP1F.pdf");
    }
    
    protected function mapData()
    {
        parent::mapData();
        
        if($this->lpa->document->primaryAttorneys[0] instanceof TrustCorporation) {
            $this->flattenLpa['attorney-0-is-trust-corporation'] = 'On';
            $this->flattenLpa['lpa-document-primaryAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-primaryAttorneys-0-name'];
        }
        
        if($this->lpa->document->replacementAttorneys[0]  instanceof TrustCorporation) {
            $this->flattenLpa['replacement-attorney-0-is-trust-corporation'] = 'On';
            $this->flattenLpa['lpa-document-replacementAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-replacementAttorneys-0-name'];
        }
        
        if($this->lpa->document->decisions->when == Decisions::LPA_DECISION_WHEN_NOW) {
            $this->flattenLpa['attorneys-may-make-decisions-when-lpa-registered'] = 'On';
        }
        elseif($this->lpa->document->decisions->when == Decisions::LPA_DECISION_WHEN_NO_CAPACITY) {
            $this->flattenLpa['attorneys-may-make-decisions-when-donor-lost-mental-capacity'] = 'On';
        }
        
        return $this->flattenLpa;
    }
    
    protected function attachAdditionalPages()
    {
        
    }
} // class