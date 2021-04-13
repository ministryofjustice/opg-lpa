<?php
namespace ApplicationTest\Model\Service\Payment\Helper;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Payment\Helper\LpaIdHelper;

/**
 * Payment test case.
 */
class PaymentTest extends AbstractHttpControllerTestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function testPadLpaIdWithStringWhenZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId('123'),
            '00000000123'
        );
    }

    public function testPadLpaIdWithIntegerWhenZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId(123),
            '00000000123'
        );
    }

    public function testPadLpaIdWhenNoZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId('12345678901'),
            '12345678901'
        );
    }

    public function testPadLpaIdWhenLpaIdIsTooBig()
    {
        $exceptionThrown = false;
        try {
            $paddedId = LpaIdHelper::padLpaId('123456789011');
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    public function testConstructPaymentTransactionId()
    {
        $id = LpaIdHelper::constructPaymentTransactionId('123');

        $parts = explode('-', $id);

        $this->assertTrue(count($parts) == 2);

        $this->assertEquals(
            '00000000123',
            $parts[0]
        );

        $this->assertTrue(is_numeric($parts[1]));
    }
}
