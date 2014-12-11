<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Formatter;

class Lpa120 extends AbstractForm
{
    private $basePdfTemplate;
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LPA120-' . microtime(true) . '.pdf';
        
        $this->basePdfTemplate = $this->basePdfTemplatePath."/LPA120.pdf";
    }
    
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     * 
     * @return Form object
     */
    public function generate()
    {
        $pdf = PdfProcessor::getPdftkInstance($this->basePdfTemplate);
        
        $this->generatedPdfFilePath = $this->registerTempFile('LPA120');
        
        // populate forms
        $mappings = $this->dataMappingForStandardForm();
        print_r($mappings);
        $pdf->fillForm($mappings)
	        ->needAppearances()
            ->flatten()
            ->saveAs($this->generatedPdfFilePath);
        
        return $this;
        
    } // function generate()
    
    /**
     * Data mapping
     * 
     * @return array
     */
    protected function dataMappingForStandardForm()
    {
        $mappings = array(
                'donor-full-name'   => $this->fullName($this->lpa->document->donor->name),
                'donor-address'     => implode(', ', array(
                        $this->lpa->document->donor->address->address1,
                        $this->lpa->document->donor->address->address2,
                        $this->lpa->document->donor->address->address3,
                        $this->lpa->document->donor->address->postcode
                )),
                'lpa-type-property-and-financial-affairs' => ($this->lpa->document->type==Document::LPA_TYPE_PF)?self::CHECK_BOX_ON:null,
                'lpa-type-health-and-welfare'             => ($this->lpa->document->type==Document::LPA_TYPE_HW)?self::CHECK_BOX_ON:null,
                'is-repeat-application'     => ($this->lpa->repeatCaseNumber==null)?null:self::CHECK_BOX_ON,
                'correspondent'             => $this->lpa->document->correspondent->who,
                'correspondent-name-title'  => strtolower($this->lpa->document->correspondent->name->title),
                'correspondent-name-first'  => $this->lpa->document->correspondent->name->first,
                'correspondent-name-last'   => $this->lpa->document->correspondent->name->last,
                'correspondent-address'     => implode(', ', array(
                        $this->lpa->document->correspondent->address->address1,
                        $this->lpa->document->correspondent->address->address2,
                        $this->lpa->document->correspondent->address->address3,
                        $this->lpa->document->correspondent->address->postcode
                )),
                'correspondent-phone'       => $this->lpa->document->correspondent->phone->number,
                'correspondent-name-email-address' => $this->lpa->document->correspondent->email->address,
                'receive-benefits'          => $this->lpa->payment->reducedFeeReceivesBenefits?'yes':'no',
                'damage-awarded'            => $this->lpa->payment->reducedFeeAwardedDamages?'yes':'no',
                'low-income'                => $this->lpa->payment->reducedFeeLowIncome?'yes':'no',
                'receive-universal-credit'  => $this->lpa->payment->reducedFeeUniversalCredit?'yes':'no'
        );
        
        return $mappings;
    } // function dataMappingForStandardForm()
}
