<?php
namespace Opg\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Calculator
{
    const STANDARD_FEE = 110;

    /**
     * Calculate LPA payment amount
     *
     * @param Lpa $lpa
     * @return NULL|Payment
     */
    static public function calculate(Lpa $lpa)
    {
        if(!($lpa->payment instanceof Payment)) return null;

        $isRepeatApplication = ($lpa->repeatCaseNumber != null);

        if(($lpa->payment->reducedFeeReceivesBenefits) && ($lpa->payment->reducedFeeAwardedDamages)) {

            $amount = self::getBenefitsFee();

        } else {

            if($lpa->payment->reducedFeeUniversalCredit) {
                $amount = null;
            }
            elseif($lpa->payment->reducedFeeLowIncome) {
                $amount = self::getLowIncomeFee( $isRepeatApplication );
            }
            else {
                $amount = self::getFullFee( $isRepeatApplication );
            }

        }

        $lpa->payment->amount = $amount;

        return $lpa->payment;
    }

    public static function getFullFee( $isRepeatApplication = false ){

        $denominator = ($isRepeatApplication) ? 2 : 1;

        return (float) self::STANDARD_FEE / $denominator;

    }

    public static function getLowIncomeFee( $isRepeatApplication = false ){

        return (float) self::getFullFee( $isRepeatApplication ) / 2;

    }

    public static function getBenefitsFee(){

        return (float)0;

    }

}
