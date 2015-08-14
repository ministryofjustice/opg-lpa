<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Logger\Logger;

class CoversheetRegistration extends AbstractForm
{
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }
    
    public function generate()
    {
        Logger::getInstance()->info(
            'Generating Coversheet Registration',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        $filePath = $this->registerTempFile('Coversheet');
        
        $coversheetRegistration = PdfProcessor::getPdftkInstance($this->pdfTemplatePath.'/LP1_CoversheetRegistration.pdf');
        
        $coversheetRegistration->fillForm(
            array(
                    'lpa-number' => \Opg\Lpa\DataModel\Lpa\Formatter::id($this->lpa->id).'.',
            ))
        ->flatten()
        ->saveAs($filePath);
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class CoversheetRegistration