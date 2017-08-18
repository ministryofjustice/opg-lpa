<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

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
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS4');

        $this->pdfFormData['cs4-trust-corporation-company-registration-number'] = $this->companyNumber;
        $this->pdfFormData['cs4-footer-right'] = Config::getInstance()['footer']['cs4'];

        $this->pdf = new Pdf($this->pdfTemplatePath.'/LPC_Continuation_Sheet_4.pdf');

        $this->pdf->fillForm($this->pdfFormData)
                  ->flatten()
                  ->saveAs($filePath);

        return $this->interFileStack;
    }
}
