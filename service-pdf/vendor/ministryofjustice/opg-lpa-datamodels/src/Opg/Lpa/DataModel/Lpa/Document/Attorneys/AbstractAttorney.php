<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

abstract class AbstractAttorney extends AbstractData {

    protected $address;
    protected $email;

    public function __construct(){
        parent::__construct();

        $this->address = new Elements\Address();
        $this->email = new Elements\EmailAddress();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

    } // function

} // abstract class
