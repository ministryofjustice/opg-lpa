<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Document\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Human extends AbstractAttorney {

    protected $name;
    protected $dob;

    public function __construct(){
        parent::__construct();

        $this->name = new Elements\Name();
        $this->dob = new Elements\Dob();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

    } // function

} // class
