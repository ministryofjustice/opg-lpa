<?php
namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a postal address.
 *
 * Class Address
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Address extends AbstractData {

    /**
     * @var string First line of the address.
     */
    protected $address1;

    /**
     * @var string Second line of the address.
     */
    protected $address2;

    /**
     * @var string Third line of the address.
     */
    protected $address3;

    /**
     * @var string A UK postcode.
     */
    protected $postcode;

    /**
     * @var string ISO 3166-1 alpha-2 country code
     */
    protected $country;


    public function __construct( $data = null ){

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

        $this->validators['country'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 2, 2, true ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
