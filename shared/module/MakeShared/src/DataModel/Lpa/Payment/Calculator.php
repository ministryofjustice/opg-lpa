<?php

namespace MakeShared\DataModel\Lpa\Payment;

use MakeShared\DataModel\Lpa\Lpa;

class Calculator
{
    public const STANDARD_FEE = 92;

    /**
     * Calculate LPA payment amount
     *
     * @param Lpa $lpa
     * @return NULL|Payment
     */
    public static function calculate(Lpa $lpa): ?Payment
    {
        if (!$lpa->getPayment() instanceof Payment) {
            return null;
        }

        if ($lpa->getPayment()->isReducedFeeReceivesBenefits() && $lpa->getPayment()->isReducedFeeAwardedDamages()) {
            $amount = self::getBenefitsFee();
        } else {
            $isRepeatApplication = ($lpa->getRepeatCaseNumber() != null);

            if ($lpa->getPayment()->isReducedFeeUniversalCredit()) {
                $amount = null;
            } elseif ($lpa->getPayment()->isReducedFeeLowIncome()) {
                $amount = self::getLowIncomeFee($isRepeatApplication);
            } else {
                $amount = self::getFullFee($isRepeatApplication);
            }
        }

        $lpa->getPayment()->setAmount($amount);
        return $lpa->getPayment();
    }

    public static function getFullFee(bool $isRepeatApplication = false): int
    {
        return $isRepeatApplication ? self::repeatApplicationFee() : self::baseFee();
    }

    public static function getLowIncomeFee(bool $isRepeatApplication = false): float|int
    {
        return self::getFullFee($isRepeatApplication) / 2;
    }

    public static function getBenefitsFee(): float|int
    {
        return 0.0;
    }

    public static function baseFee(): int
    {
        return self::STANDARD_FEE;
    }

    public static function halfFee(): int
    {
        return intdiv(self::baseFee(), 2);
    }

    public static function repeatApplicationFee(): int
    {
        return self::halfFee();
    }
}
