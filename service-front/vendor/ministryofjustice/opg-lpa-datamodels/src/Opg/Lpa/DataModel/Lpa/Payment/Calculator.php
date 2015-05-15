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
     * @return NULL|\Opg\Lpa\DataModel\Lpa\Payment
     */
    static public function calculate(Lpa $lpa)
    {
        if(!($lpa->payment instanceof Payment)) return null;
        
        if(($lpa->payment->reducedFeeReceivesBenefits) && ($lpa->payment->reducedFeeAwardedDamages)) {
            $amount = (float) 0;
        }
        else {
            if($lpa->payment->reducedFeeUniversalCredit) {
                $amount = null;
            }
            elseif($lpa->payment->reducedFeeLowIncome) {
                $amount = (float) self::STANDARD_FEE/2;
            }
            else {
                $amount = (float) self::STANDARD_FEE;
            }
            
            if($lpa->repeatCaseNumber != null) {
                $amount = $amount/2;
            }
        }
        
        $lpa->payment->amount = $amount;
        
        return $lpa->payment;
    }
}
