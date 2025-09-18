<?php

namespace ApplicationTest\Model\Service\Payment\Helper;

use Exception;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Payment\Helper\LpaIdHelper;

/**
 * Payment test case.
 */
final class LpaIdHelperTest extends AbstractHttpControllerTestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
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
        } catch (Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    public function testConstructPaymentTransactionId()
    {
        $id = LpaIdHelper::constructPaymentTransactionId('123');

        $this->assertEquals(
            '00000000123',
            $id
        );

        $this->assertTrue(is_numeric($id));
    }
}
