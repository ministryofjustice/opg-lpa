<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Config\Config;

class Lp3 extends AbstractForm
{

    public function __construct(Lpa $lpa, Config $config)
    {
        parent::__construct($lpa);
        $this->pdf = new Pdf($config['service']['assets']['path']."/v2/LP3.pdf");
    }
    
    protected function mapData()
    {
        
    }
}