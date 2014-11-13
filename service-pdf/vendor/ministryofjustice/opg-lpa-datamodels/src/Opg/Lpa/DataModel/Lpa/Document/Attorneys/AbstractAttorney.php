<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

abstract class AbstractAttorney extends AbstractData {

    protected $address;
    protected $email;

    public function __construct( $data ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['address'] = function($v){
            return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        };

        $this->typeMap['email'] = function($v){
            return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Address' ),
            ]);
        };

        $this->validators['email'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\EmailAddress' ),
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function

} // abstract class
