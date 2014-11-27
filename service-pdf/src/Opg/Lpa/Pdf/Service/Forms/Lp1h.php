<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Formatter;
use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Config\Config;

class Lp1h extends Lp1
{

    public function __construct (Lpa $lpa, Config $config)
    {
        parent::__construct($lpa);
        
        $this->basePdfTemplatePath = $config['service']['assets']['path'].'/v2';
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP1F-' . time() . '.pdf';
        
        $this->pdf = new Pdf($this->basePdfTemplatePath.'/LP1H.pdf');
    }
    
    public function mapData()
    {
        parent::mapData();
        if($this->flattenLpa['lpa-document-primaryAttorneyDecisions-canSustainLife'] === true) {
            $this->strokePages[5] = array('life-sustain-B');
        }
        else {
            $this->strokePages[5] = array('life-sustain-A');
        }
    }
} // class