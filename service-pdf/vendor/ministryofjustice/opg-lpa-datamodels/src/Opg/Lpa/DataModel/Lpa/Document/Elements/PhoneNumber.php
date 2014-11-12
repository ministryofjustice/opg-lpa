<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class PhoneNumber extends AbstractData {

    protected $number;

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->number = '020 1234 5678';

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['number'] = function(){
            return (new Validator)->addRules([
                new Rules\Phone,
            ]);
        };

    } // function

} // class
