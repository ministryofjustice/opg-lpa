<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\Pdf\Logger\Logger;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Lpa120 extends AbstractForm
{
    private $basePdfTemplate;
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LPA120');
        
        $this->basePdfTemplate = $this->pdfTemplatePath."/LPA120.pdf";
    }
    
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     * 
     * @return Form object | null
     */
    public function generate()
    {
        Logger::getInstance()->info(
            'Generating Lpa120',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        // check eligibility for exemption or remission.
        if(!$this->lpa->repeatCaseNumber &&
            !$this->lpa->payment->reducedFeeLowIncome && 
            !($this->lpa->payment->reducedFeeReceivesBenefits && $this->lpa->payment->reducedFeeAwardedDamages) &&
            !$this->lpa->payment->reducedFeeUniversalCredit) {
                throw new \RuntimeException("LPA120 is not available for this LPA.");
            }
        
        $pdf = PdftkInstance::getInstance($this->basePdfTemplate);
        
        $this->generatedPdfFilePath = $this->registerTempFile('LPA120');
        
        // populate forms
        $mappings = $this->dataMapping();
        
        $pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($this->generatedPdfFilePath);

        $this->protectPdf();
        
        return $this;
        
    } // function generate()
    
    /**
     * Data mapping
     * 
     * @return array
     */
    protected function dataMapping()
    {
        if($this->lpa->payment->reducedFeeReceivesBenefits === true) {
            $benefits = 'yes';
        }
        elseif($this->lpa->payment->reducedFeeReceivesBenefits === false) {
            $benefits = 'no';
        }
        else {
            $benefits = null;
        }
        
        if($this->lpa->payment->reducedFeeAwardedDamages === true) {
            $damages = 'no';
        }
        elseif($this->lpa->payment->reducedFeeAwardedDamages === false) {
            $damages = 'yes';
        }
        else {
            $damages = null;
        }
        
        if($this->lpa->payment->reducedFeeLowIncome === true) {
            $income = 'yes';
        }
        elseif($this->lpa->payment->reducedFeeLowIncome === false) {
            $income = 'no';
        }
        else {
            $income = null;
        }
        
        if($this->lpa->payment->reducedFeeUniversalCredit === true) {
            $uc = 'yes';
        }
        elseif($this->lpa->payment->reducedFeeUniversalCredit === false) {
            $uc = 'no';
        }
        else {
            $uc = null;
        }
        
        // get applicant object
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $applicant = $this->lpa->document->donor;
            $applicantType = 'donor';
        }
        else {
            if(!is_array($this->lpa->document->whoIsRegistering)) {
                throw new \Exception('When generating LAP120, applicant was found invalid');
            }
            
            // get the first element in the whoIsRegistering array as the applicant of the LPA.
            foreach($this->lpa->document->whoIsRegistering as $attorneyId) {
                $applicant = $this->lpa->document->getPrimaryAttorneyById($attorneyId);
                $applicantType = 'attorney';
                break;
            }
        }
        
        // convert address object to array and remove empty field.
        $address = [];
        if(($applicant->address->address1!=null)&&($applicant->address->address1!='')) $address[] = $applicant->address->address1;
        if(($applicant->address->address2!=null)&&($applicant->address->address2!='')) $address[] = $applicant->address->address2;
        if(($applicant->address->address3!=null)&&($applicant->address->address3!='')) $address[] = $applicant->address->address3;
        if(($applicant->address->postcode!=null)&&($applicant->address->postcode!='')) $address[] = $applicant->address->postcode;
        
        if($applicant->name instanceof Name) {
            $applicantNameTitle = strtolower($applicant->name->title);
            if(!in_array($applicantNameTitle, ['mr','mrs','miss','ms'])) {
                $applicantNameTitle = 'other';
            }
        }
        
        $mappings = array(
                'donor-full-name'   => $this->fullName($this->lpa->document->donor->name),
                'donor-address'     => "\n".implode(', ', array(
                        $this->lpa->document->donor->address->address1,
                        $this->lpa->document->donor->address->address2,
                        $this->lpa->document->donor->address->address3,
                        $this->lpa->document->donor->address->postcode
                )),
                'lpa-type' => ($this->lpa->document->type==Document::LPA_TYPE_PF)?'property-and-financial-affairs':'health-and-welfare',
                'is-repeat-application'     => ($this->lpa->repeatCaseNumber===null)?null:self::CHECK_BOX_ON,
                'case-number'               => $this->lpa->repeatCaseNumber,
                'applicant-type'             => $applicantType,
                'applicant-name-title'  => $applicantNameTitle,
                'applicant-name-title-other'  => ($applicantNameTitle=='other')?$applicant->name->title:null,
                'applicant-name-first'  => ($applicant->name instanceof Name)?$applicant->name->first:null,
                'applicant-name-last'   => ($applicant->name instanceof Name)?$applicant->name->last:$applicant->name,
                'applicant-address'     => "\n".implode(', ', $address),
                'applicant-email-address' => ($applicant->email instanceof EmailAddress)?$applicant->email->address:null,
                'receive-benefits'          => $benefits,
                'damage-awarded'            => $damages,
                'low-income'                => $income,
                'receive-universal-credit'  => $uc,
        );
        
        return $mappings;
    } // function dataMapping()
}  // class Lpa120