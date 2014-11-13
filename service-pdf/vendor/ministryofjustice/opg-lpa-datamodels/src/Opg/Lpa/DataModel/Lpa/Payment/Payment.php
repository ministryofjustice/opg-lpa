<?php
namespace Opg\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;
use Opg\Lpa\DataModel\Lpa\Elements;

class Payment extends AbstractData {

    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_CHEQUE = 'cheque';

    protected $method;
    protected $phone;
    protected $amount;
    protected $reference;

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['phone'] = function($v){
            return ($v instanceof Elements\PhoneNumber) ? $v : new Elements\PhoneNumber( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['method'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( [ self::PAYMENT_TYPE_CARD, self::PAYMENT_TYPE_CHEQUE ], true ),
            ]);
        };

        $this->validators['reference'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['amount'] = function(){
            return (new Validator)->addRules([
                new Rules\Float,
            ]);
        };

        $this->validators['reference'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\String,
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function


} // class
