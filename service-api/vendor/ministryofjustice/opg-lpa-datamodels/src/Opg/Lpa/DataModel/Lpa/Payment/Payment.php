<?php
namespace Opg\Lpa\DataModel\Lpa\Payment;

use DateTime;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

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
     * null = The amount it undecided.
     * 0 = The donor does not need to pay.
     *
     * @var null|float The amount that has or should be charged.
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
     * @var bool Has the donor received a personal injury payout, less then the required threshold.
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

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('method', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Choice([ 'choices' => [ self::PAYMENT_TYPE_CARD, self::PAYMENT_TYPE_CHEQUE ] ]),
        ]);

        $metadata->addPropertyConstraints('phone', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('amount', [
            new Assert\Type([ 'type' => 'float' ]),
            new Assert\Range([ 'min' => 0 ]),
        ]);

        $metadata->addPropertyConstraints('reference', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 32 ]),
        ]);

        $metadata->addPropertyConstraints('date', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('reducedFeeReceivesBenefits', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeAwardedDamages', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeLowIncome', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeUniversalCredit', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

    } // function

    //------------------------------------------------

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map( $property, $v ){

        switch( $property ){
            case 'date':
                return ($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime( $v );
            case 'phone':
                return ($v instanceof Elements\PhoneNumber) ? $v : new Elements\PhoneNumber( $v );
            case 'amount':
                return ( !is_int( $v ) ) ? $v : (float)$v;
        }

        // else...
        return parent::map( $property, $v );

    } // function

} // class
