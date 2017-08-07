<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class Cs3 extends AbstractForm
{

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS3');

        $this->pdfFormData['cs3-donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
        $this->pdfFormData['cs3-footer-right'] = Config::getInstance()['footer']['cs3'];

        $this->pdf = PdftkInstance::getInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_3.pdf");

        $this->pdf->fillForm($this->pdfFormData)
                  ->flatten()
                  ->saveAs($filePath);

        return $this->interFileStack;
    } // function generate()

    public function __destruct()
    {
    }
}