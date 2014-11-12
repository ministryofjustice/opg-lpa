<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class CertificateProvider extends AbstractData {

    protected $name;
    protected $address;


    public function __construct(){
        parent::__construct();

        $this->name = new Elements\Name();
        $this->address = new Elements\Address();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)


    } // function

} // class
