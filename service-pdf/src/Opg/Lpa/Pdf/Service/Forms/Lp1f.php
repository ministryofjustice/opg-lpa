<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;
use mikehaertl\pdftk\pdf as Pdf;
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

    protected function hasAdditionalPages ()
    {
        if (parent::hasAdditionalPages()) {
            return true;
        }
        
        // if a trust corp is a primary attorney attorney - CS4
        if ($this->lpa->document->primaryAttorneys[0] instanceof TrustCorporation) {
            return true;
        }
        
        // if a trust corp is a replacement attorney - CS4
        if ((count($this->lpa->document->replacementAttorneys) > 0) &&
                 ($this->lpa->document->replacementAttorneys[0] instanceof TrustCorporation)) {
            return true;
        }
    }

    protected function dataMappingForStandardForm ()
    {
        parent::dataMappingForStandardForm();
        
        if ($this->lpa->document->primaryAttorneys[0] instanceof TrustCorporation) {
            $this->flattenLpa['attorney-0-is-trust-corporation'] = 'On';
            $this->flattenLpa['lpa-document-primaryAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-primaryAttorneys-0-name'];
        }
        
        if ($this->lpa->document->replacementAttorneys[0] instanceof TrustCorporation) {
            $this->flattenLpa['replacement-attorney-0-is-trust-corporation'] = 'On';
            $this->flattenLpa['lpa-document-replacementAttorneys-0-name-last'] = $this->flattenLpa['lpa-document-replacementAttorneys-0-name'];
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
        
        return $this->flattenLpa;
    }

    protected function generateAdditionalPages ()
    {
        parent::generateAdditionalPages();
        
        // CS4
        if ($this->lpa->document->primaryAttorneys[0] instanceof TrustCorporation) {
            $this->addContinuationSheet4(
                    $this->lpa->document->primaryAttorneys[0]->number);
        } elseif ((count($this->lpa->document->replacementAttorneys) > 0) &&
                 ($this->lpa->document->replacementAttorneys[0] instanceof TrustCorporation)) {
            $this->addContinuationSheet4(
                    $this->lpa->document->replacementAttorneys[0]->number);
        }
    }

    /**
     * Fill the trust corporation registration number.
     */
    protected function addContinuationSheet4 ($trustCorpRegNumber)
    {
        $tmpSavePath = '/tmp/pdf-CS4-' . $this->lpa->id . '-' . microtime() . '.pdf';
        $this->intermediateFilePaths['CS4'] = $tmpSavePath;
        
        $cs2 = new Pdf($this->basePdfTemplatePath.'/LPC_Continuation_Sheet_4.pdf');
        
        $cs2->fillForm(
                array(
                        'cs-4-trust-corporation-company-registration-number' => $trustCorpRegNumber
                ))
            ->needAppearances()
            ->saveAs($tmpSavePath);
    } // function addContinuationSheet4()
} // class