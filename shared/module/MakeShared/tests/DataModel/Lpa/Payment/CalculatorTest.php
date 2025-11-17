<?php

namespace MakeSharedTest\DataModel\Lpa\Payment;

use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private int $fee;

    public function setUp(): void
    {
        parent::setUp();

        $this->fee = 92;
    }

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
        $this->assertEquals($this->fee / 2, $payment->get('amount'));
    }

    public function testCalculateFullFee()
    {
        $lpa = FixturesData::getPfLpa();

        $payment = Calculator::calculate($lpa);

        $this->assertEquals('cheque', $payment->get('method'));
        $this->assertEquals($this->fee, $payment->get('amount'));
    }

    public function testGetFullFee()
    {
        $fee = Calculator::getFullFee();

        $this->assertEquals($this->fee, $fee);
    }

    public function testGetFullFeeRepeatApplication()
    {
        $fee = Calculator::getFullFee(true);

        $this->assertEquals($this->fee / 2, $fee);
    }

    public function testGetLowIncomeFee()
    {
        $fee = Calculator::getLowIncomeFee();

        $this->assertEquals($this->fee / 2, $fee);
    }

    public function testGetLowIncomeFeeRepeatApplication()
    {
        $fee = Calculator::getLowIncomeFee(true);

        $this->assertEquals($this->fee / 4, $fee);
    }

    public function testGetBenefitsFee()
    {
        $fee = Calculator::getBenefitsFee();

        $this->assertEquals(0.0, $fee);
    }
}
