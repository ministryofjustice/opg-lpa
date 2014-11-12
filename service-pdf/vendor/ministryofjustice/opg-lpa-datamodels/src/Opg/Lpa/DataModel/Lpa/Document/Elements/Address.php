<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Address extends AbstractData {

    protected $address1;
    protected $address2;
    protected $address3;
    protected $postcode;

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->address1 = 'Line 1';
        $this->address2 = 'Line 2';
        $this->address3 = 'A new line';
        $this->postcode = 'SW1H 9AJ';

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['address1'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 50, true ),
            ]);
        };

        $this->validators['address2'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\Length( 1, 50, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

        $this->validators['address3'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\Length( 1, 50, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

        $this->validators['postcode'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\Length( 5, 8, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

    } // function

} // class
