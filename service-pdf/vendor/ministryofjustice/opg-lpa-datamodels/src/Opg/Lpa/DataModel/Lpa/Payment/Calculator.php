<?php

namespace Opg\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Calculator
{
    const STANDARD_FEE = 82;

    /**
     * Calculate LPA payment amount
     *
     * @param Lpa $lpa
     * @return NULL|Payment
     */
    public static function calculate(Lpa $lpa)
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

    public static function getFullFee($isRepeatApplication = false)
    {
        $fee = self::STANDARD_FEE / ($isRepeatApplication ? 2 : 1);

        return (float) $fee;
    }

    public static function getLowIncomeFee($isRepeatApplication = false)
    {
        return (float) self::getFullFee($isRepeatApplication) / 2;
    }

    public static function getBenefitsFee()
    {
        return 0.0;
    }
}
