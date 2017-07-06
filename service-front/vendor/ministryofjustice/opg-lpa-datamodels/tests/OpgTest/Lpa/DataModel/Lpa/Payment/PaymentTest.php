<?php

namespace OpgTest\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $payment = FixturesData::getPayment();

        $this->assertEquals(82, $payment->get('amount'));
        $this->assertEquals(new \DateTime('2017-03-24T16:21:52.804000+0000'), $payment->get('date'));
        $this->assertEquals('test@payment.com', $payment->get('email')->get('address'));
    }

    public function testValidation()
    {
        $payment = FixturesData::getPayment();

        $validatorResponse = $payment->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $address = new Payment();
        $address->set('method', 'Invalid');
        $address->set('amount', -1);
        $address->set('reference', FixturesData::generateRandomString(33));
        $address->set('gatewayReference', FixturesData::generateRandomString(65));

        $validatorResponse = $address->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(4, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['method']);
        $this->assertNotNull($errors['amount']);
        $this->assertNotNull($errors['reference']);
        $this->assertNotNull($errors['gatewayReference']);
    }
}
