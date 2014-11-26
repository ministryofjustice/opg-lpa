<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;
use mikehaertl\pdftk\pdf as Pdf;

class Lp1h extends Lp1
{

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP1F-' . time() . '.pdf';
        
        $this->pdf = new Pdf(array(
                'A' => PDF_TEMPLATE_PATH."/LP1H.pdf"
        ));
    }
} // class