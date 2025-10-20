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
    public const PAYMENT_TYPE_CARD = 'card';
    public const PAYMENT_TYPE_CHEQUE = 'cheque';

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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('method', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Choice([
                'choices' => [
                    self::PAYMENT_TYPE_CARD,
                    self::PAYMENT_TYPE_CHEQUE
                ]
            ]),
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([
                'type' => '\MakeShared\DataModel\Common\EmailAddress'
            ]),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('amount', [
            new Assert\Type([
                'type' => 'float'
            ]),
            new Assert\Range([
                'min' => 0
            ]),
        ]);

        $metadata->addPropertyConstraints('reference', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => 32
            ]),
        ]);

        $metadata->addPropertyConstraints('gatewayReference', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => 64
            ]),
        ]);

        $metadata->addPropertyConstraints('date', [
            new Assert\Custom\DateTimeUTC(),
        ]);

        $metadata->addPropertyConstraints('reducedFeeReceivesBenefits', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeAwardedDamages', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeLowIncome', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('reducedFeeUniversalCredit', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
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
     * @param string $method
     * @return $this
     */
    public function setMethod($method): Payment
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email): Payment
    {
        $this->email = $email;

        return $this;
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
     * @param string $reference
     * @return $this
     */
    public function setReference($reference): Payment
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return string
     */
    public function getGatewayReference()
    {
        return $this->gatewayReference;
    }

    /**
     * @param string $gatewayReference
     * @return $this
     */
    public function setGatewayReference($gatewayReference): Payment
    {
        $this->gatewayReference = $gatewayReference;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return $this
     */
    public function setDate($date): Payment
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReducedFeeReceivesBenefits()
    {
        return $this->reducedFeeReceivesBenefits;
    }

    /**
     * @param bool $reducedFeeReceivesBenefits
     * @return $this
     */
    public function setReducedFeeReceivesBenefits($reducedFeeReceivesBenefits): Payment
    {
        $this->reducedFeeReceivesBenefits = $reducedFeeReceivesBenefits;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReducedFeeAwardedDamages()
    {
        return $this->reducedFeeAwardedDamages;
    }

    /**
     * @param bool $reducedFeeAwardedDamages
     * @return $this
     */
    public function setReducedFeeAwardedDamages($reducedFeeAwardedDamages): Payment
    {
        $this->reducedFeeAwardedDamages = $reducedFeeAwardedDamages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReducedFeeLowIncome()
    {
        return $this->reducedFeeLowIncome;
    }

    /**
     * @param bool $reducedFeeLowIncome
     * @return $this
     */
    public function setReducedFeeLowIncome($reducedFeeLowIncome): Payment
    {
        $this->reducedFeeLowIncome = $reducedFeeLowIncome;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReducedFeeUniversalCredit()
    {
        return $this->reducedFeeUniversalCredit;
    }

    /**
     * @param bool $reducedFeeUniversalCredit
     * @return $this
     */
    public function setReducedFeeUniversalCredit($reducedFeeUniversalCredit): Payment
    {
        $this->reducedFeeUniversalCredit = $reducedFeeUniversalCredit;

        return $this;
    }
}
