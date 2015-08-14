<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Logger\Logger;

class Cs3 extends AbstractForm
{
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }
    
    public function generate()
    {
        Logger::getInstance()->info(
            'Generating Cs3',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        $filePath = $this->registerTempFile('CS3');
    
        $cs3 = PdfProcessor::getPdftkInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_3.pdf");
    
        $cs3->fillForm(array(
                'donor-full-name' => $this->fullName($this->lpa->document->donor->name),
                'footer_right'    => Config::getInstance()['footer']['cs3'],
        ))
        ->flatten()
        ->saveAs($filePath);
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Cs3