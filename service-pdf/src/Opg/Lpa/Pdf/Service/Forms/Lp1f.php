<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\Forms\FormAbstract;
use mikehaertl\pdftk\pdf as Pdf;

class Lp1f extends FormAbstract
{

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        $this->pdf = new Pdf("assets/v2/LP1F.pdf");
    }
    
    protected function mapData()
    {
    }
    
    protected function attachAdditionalPages()
    {
        
    }
} // class