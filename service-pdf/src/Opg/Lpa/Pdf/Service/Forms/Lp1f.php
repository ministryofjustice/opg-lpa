<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\pdf as Pdf;

class Lp1f extends Lp1
{

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedpPdfFilePath = '/tmp/pdf-'.Formatter::id($this->lpa->id).'-LP1F-'.time().'.pdf';
        
        $this->pdf = new Pdf("../assets/v2/LP1F.pdf");
    }
    
    protected function mapData()
    {
        parent::mapData();
        
        $this->flattenLpa['attorney-1-is-trust-corporation'] = 'Off';
        
        
        return $this->flattenLpa;
    }
    
    protected function attachAdditionalPages()
    {
        
    }
} // class