<?php

declare(strict_types=1);

namespace MakeShared\DataModel\Lpa\Payment;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
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
    public const string PAYMENT_TYPE_CARD = 'card';
    public const string PAYMENT_TYPE_CHEQUE = 'cheque';

    /**
     * The payment method used (or that will be used).
     */
    protected ?string $method = null;

    /**
     * The email address that should be used regarding payment.
     */
    protected ?string $email = null;

    /**
     * null = The amount it undecided.
     * 0 = The donor does not need to pay.
     *
     * The amount that has or should be charged.
     */
    protected ?float $amount = null;

    /**
     * The OPG payment reference number.
     */
    protected ?string $reference = null;

    /**
     * The payment gateway reference.
     */
    protected ?string $gatewayReference = null;

    /**
     * Date the payment was made.
     */
    protected ?\DateTime $date = null;

    /**
     * Does the donor receive any qualifying benefits.
     */
    protected ?bool $reducedFeeReceivesBenefits = null;

    /**
     * Has the donor received a personal injury payout, less then the required threshold.
     */
    protected ?bool $reducedFeeAwardedDamages = null;

    /**
     * Does the donor have what is considered a low income.
     */
    protected ?bool $reducedFeeLowIncome = null;

    /**
     * Does the donor receive Universal Credit.
     */
    protected ?bool $reducedFeeUniversalCredit = null;

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraints('method', [
            new Assert\Type('string'),
            new Assert\Choice(
                choices: [
                    self::PAYMENT_TYPE_CARD,
                    self::PAYMENT_TYPE_CHEQUE
                ]
            ),
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type('string'),
        ]);

        $metadata->addPropertyConstraints('amount', [
            new Assert\Type('float'),
            new Assert\Range(
                min: 0
            ),
        ]);

        $metadata->addPropertyConstraints('reference', [
            new Assert\Type('string'),
            new Assert\Length(
                max: 32
            ),
        ]);

        $metadata->addPropertyConstraints('gatewayReference', [
            new Assert\Type('string'),
            new Assert\Length(
                max: 64
            ),
        ]);

        $metadata->addPropertyConstraints('date', [
            new Assert\Custom\DateTimeUTC(),
        ]);

        $metadata->addPropertyConstraints('reducedFeeReceivesBenefits', [
            new Assert\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('reducedFeeAwardedDamages', [
            new Assert\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('reducedFeeLowIncome', [
            new Assert\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('reducedFeeUniversalCredit', [
            new Assert\Type('bool'),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     * @return mixed Mapped value.
     * @throws \DateMalformedStringException
     */
    protected function map($property, $value): mixed
    {
        switch ($property) {
            case 'date':
                return (($value instanceof \DateTime || is_null($value)) ? $value : new \DateTime($value));
            case 'amount':
                return (!is_int($value) ? $value : (float)$value);
        }

        return parent::map($property, $value);
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): Payment
    {
        $this->method = $method;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): Payment
    {
        $this->email = $email;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): Payment
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): Payment
    {
        $this->reference = $reference;

        return $this;
    }

    public function getGatewayReference(): ?string
    {
        return $this->gatewayReference;
    }

    public function setGatewayReference(?string $gatewayReference): Payment
    {
        $this->gatewayReference = $gatewayReference;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): Payment
    {
        $this->date = $date;

        return $this;
    }

    public function isReducedFeeReceivesBenefits(): ?bool
    {
        return $this->reducedFeeReceivesBenefits;
    }

    public function setReducedFeeReceivesBenefits(?bool $reducedFeeReceivesBenefits): Payment
    {
        $this->reducedFeeReceivesBenefits = $reducedFeeReceivesBenefits;

        return $this;
    }

    public function isReducedFeeAwardedDamages(): ?bool
    {
        return $this->reducedFeeAwardedDamages;
    }

    public function setReducedFeeAwardedDamages(?bool $reducedFeeAwardedDamages): Payment
    {
        $this->reducedFeeAwardedDamages = $reducedFeeAwardedDamages;

        return $this;
    }

    public function isReducedFeeLowIncome(): ?bool
    {
        return $this->reducedFeeLowIncome;
    }

    public function setReducedFeeLowIncome(?bool $reducedFeeLowIncome): Payment
    {
        $this->reducedFeeLowIncome = $reducedFeeLowIncome;

        return $this;
    }

    public function isReducedFeeUniversalCredit(): ?bool
    {
        return $this->reducedFeeUniversalCredit;
    }

    public function setReducedFeeUniversalCredit(?bool $reducedFeeUniversalCredit): Payment
    {
        $this->reducedFeeUniversalCredit = $reducedFeeUniversalCredit;

        return $this;
    }
}
