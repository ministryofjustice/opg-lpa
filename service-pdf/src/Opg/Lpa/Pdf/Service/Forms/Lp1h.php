<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\pdf as Pdf;

class Lp1h extends Lp1
{

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        $this->pdf = new Pdf(array(
                'A' => "assets/v2/LP1H.pdf"
        ));
    }
} // class