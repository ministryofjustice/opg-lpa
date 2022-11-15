<?php

namespace MakeShared\DataModel\Lpa\Payment;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents payment information associated with an LPA.
 *
 * Class Payment
 * @package MakeShared\DataModel\Lpa\Payment
 */
class Payment extends AbstractData
{
    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_CHEQUE = 'cheque';

    /**
     * @var string The payment method used (or that will be used).
     */
    protected $method;

    /**
     * @var string The email address that should be used regarding payment.
     */
    protected $email;

    /**
     * null = The amount it undecided.
     * 0 = The donor does not need to pay.
     *
     * @var null|float The amount that has or should be charged.
     */
    protected $amount;

    /**
     * @var string The OPG payment reference number.
     */
    protected $reference;

    /**
     * @var string The payment gateway reference.
     */
    protected $gatewayReference;

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

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     *
     * @return mixed Mapped value.
     */
    protected function map($property, $value)
    {
        switch ($property) {
            case 'date':
                return (($value instanceof \DateTime || is_null($value)) ? $value : new \DateTime($value));
            case 'amount':
                return (!is_int($value) ? $value : (float)$value);
            case 'email':
                return (($value instanceof EmailAddress || is_null($value)) ? $value : new EmailAddress($value));
        }

        return parent::map($property, $value);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return float|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return bool
     */
    public function isReducedFeeReceivesBenefits()
    {
        return $this->reducedFeeReceivesBenefits;
    }

    /**
     * @return bool
     */
    public function isReducedFeeAwardedDamages()
    {
        return $this->reducedFeeAwardedDamages;
    }

    /**
     * @return bool
     */
    public function isReducedFeeLowIncome()
    {
        return $this->reducedFeeLowIncome;
    }

    /**
     * @return bool
     */
    public function isReducedFeeUniversalCredit()
    {
        return $this->reducedFeeUniversalCredit;
    }
}
