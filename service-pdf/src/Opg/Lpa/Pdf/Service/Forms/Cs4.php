<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf as PdftkInstance;

class Cs4 extends AbstractForm
{
    private $companyNumber;
    
    public function __construct(Lpa $lpa, $companyNumber)
    {
        parent::__construct($lpa);
        $this->companyNumber = $companyNumber;
    }
    
    public function generate()
    {
        $filePath = $this->registerTempFile('CS4');
        
        $cs2 = new PdftkInstance($this->pdfTemplatePath.'/LPC_Continuation_Sheet_4.pdf');
        
        $cs2->fillForm(
            array(
                    'cs4-trust-corporation-company-registration-number' => $this->companyNumber,
                    'footer_right'    => Config::getInstance()['footer']['cs4'],
            ))
        ->flatten()
        ->saveAs($filePath);
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Cs4