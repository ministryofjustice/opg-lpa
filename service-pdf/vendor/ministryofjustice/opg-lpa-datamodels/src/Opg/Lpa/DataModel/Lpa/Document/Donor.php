<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Donor extends AbstractData {

    protected $name;
    protected $otherNames;
    protected $address;
    protected $dob;
    protected $email;

    public function __construct(){
        parent::__construct();

        $this->name = new Elements\Name();
        $this->otherNames = 'Fred';
        $this->address = new Elements\Address();
        $this->dob = new Elements\Dob();
        $this->email = new Elements\EmailAddress();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['otherNames'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\NotEmpty,
                    new Rules\Length( 1, 50, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

    } // function

} // class
