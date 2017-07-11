<?php

namespace OpgTest\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use OpgTest\Lpa\DataModel\FixturesData;

class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function testCalculateNullPayment()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->set('payment', null);

        $payment = Calculator::calculate($lpa);

        $this->assertNull($payment);
    }

    public function testCalculateBenefitsFee()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('reducedFeeReceivesBenefits', true);
        $lpa->get('payment')->set('reducedFeeAwardedDamages', true);

        $payment = Calculator::calculate($lpa);

        $this->assertEquals('cheque', $payment->get('method'));
        $this->assertEquals(0.0, $payment->get('amount'));
    }

    public function testCalculateUniversalCreditFee()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->get('payment')->set('reducedFeeUniversalCredit', true);

        $payment = Calculator::calculate($lpa);

        $this->assertEquals('cheque', $payment->get('method'));
        $this->assertEquals(0.0, $payment->get('amount'));
    }

    public function testCalculateLowIncomeFee()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('reducedFeeLowIncome', true);

        $payment = Calculator::calculate($lpa);

        $this->assertEquals('cheque', $payment->get('method'));
        $this->assertEquals(41, $payment->get('amount'));
    }

    public function testCalculateFullFee()
    {
        $lpa = FixturesData::getPfLpa();

        $payment = Calculator::calculate($lpa);

        $this->assertEquals('cheque', $payment->get('method'));
        $this->assertEquals(82, $payment->get('amount'));
    }

    public function testGetFullFee()
    {
        $fee = Calculator::getFullFee();

        $this->assertEquals(82, $fee);
    }

    public function testGetFullFeeRepeatApplication()
    {
        $fee = Calculator::getFullFee(true);

        $this->assertEquals(41, $fee);
    }

    public function testGetLowIncomeFee()
    {
        $fee = Calculator::getLowIncomeFee();

        $this->assertEquals(41, $fee);
    }

    public function testGetLowIncomeFeeRepeatApplication()
    {
        $fee = Calculator::getLowIncomeFee(true);

        $this->assertEquals(20.5, $fee);
    }

    public function testGetBenefitsFee()
    {
        $fee = Calculator::getBenefitsFee();

        $this->assertEquals(0.0, $fee);
    }
}
