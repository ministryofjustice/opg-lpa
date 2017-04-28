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
        if (!$lpa->payment instanceof Payment) {
            return null;
        }

        if ($lpa->payment->reducedFeeReceivesBenefits && $lpa->payment->reducedFeeAwardedDamages) {
            $amount = self::getBenefitsFee();
        } else {
            $isRepeatApplication = ($lpa->repeatCaseNumber != null);

            if ($lpa->payment->reducedFeeUniversalCredit) {
                $amount = null;
            } elseif ($lpa->payment->reducedFeeLowIncome) {
                $amount = self::getLowIncomeFee($isRepeatApplication);
            } else {
                $amount = self::getFullFee($isRepeatApplication);
            }
        }

        $lpa->payment->amount = $amount;

        return $lpa->payment;
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
