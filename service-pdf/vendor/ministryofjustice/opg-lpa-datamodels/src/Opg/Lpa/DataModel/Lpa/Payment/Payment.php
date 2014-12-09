<?php
namespace Opg\Lpa\DataModel\Lpa\Payment;

use DateTime;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;
use Opg\Lpa\DataModel\Lpa\Elements;

/**
 * Represents payment information associated with an LPA.
 *
 * Class Payment
 * @package Opg\Lpa\DataModel\Lpa\Payment
 */
class Payment extends AbstractData {

    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_CHEQUE = 'cheque';

    /**
     * @var string The payment method used (or that will be used).
     */
    protected $method;

    /**
     * @var string The phone number that should be used regarding payment.
     */
    protected $phone;

    /**
     * @var int|float The amount that has or should be charged.
     */
    protected $amount;

    /**
     * @var string A payment reference number.
     */
    protected $reference;

    /**
     * @var DateTime Date the payment was made.
     */
    protected $date;

    /**
     * @var bool Does the donor receive any qualifying benefits.
     */
    protected $reducedFeeReceivesBenefits;

    /**
     * @var bool Has the donor received a personal injury payout.
     */
    protected $reducedFeeAwardedDamages;

    /**
     * @var bool Does the donor have what is considered a low income.
     */
    protected $reducedFeeLowIncome;

    /**
     * @var bool Does the donor receive Universal Credit.
     */
    protected $reducedFeeUniversalCredit;


    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['phone'] = function($v){
            return ($v instanceof Elements\PhoneNumber) ? $v : new Elements\PhoneNumber( $v );
        };

        $this->typeMap['date'] = function($v){
            return ($v instanceof DateTime) ? $v : new DateTime( $v );
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

        $this->validators['date'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\Instance( 'DateTime' ),
                    new Rules\Call(function($input){
                        return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                    }),
                ]),
                new Rules\NullValue,
            ]));
        };

        $this->validators['reducedFeeReceivesBenefits'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Bool,
                new Rules\NullValue,
            ]));
        };

        $this->validators['reducedFeeAwardedDamages'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Bool,
                new Rules\NullValue,
            ]));
        };

        $this->validators['reducedFeeLowIncome'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Bool,
                new Rules\NullValue,
            ]));
        };

        $this->validators['reducedFeeUniversalCredit'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Bool,
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function

} // class
