<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Document\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class TrustCorporation extends AbstractAttorney {

    protected $name;
    protected $number;

    public function __construct(){
        parent::__construct();

        $this->name = 'Corp Name Limited';
        $this->number = '1234'; // int, but can start with zeros thus a string.

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

    } // function

}
