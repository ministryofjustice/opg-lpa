<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Payment\Helper;

use Exception;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Payment\Helper\LpaIdHelper;

/**
 * Payment test case.
 */
final class LpaIdHelperTest extends AbstractHttpControllerTestCase
{
    public function testPadLpaIdWithStringWhenZeroesNeeded(): void
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId('123'),
            '00000000123'
        );
    }

    public function testPadLpaIdWithIntegerWhenZeroesNeeded(): void
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId(123),
            '00000000123'
        );
    }

    public function testPadLpaIdWhenNoZeroesNeeded(): void
    {
        $this->assertEquals(
            LpaIdHelper::padLpaId('12345678901'),
            '12345678901'
        );
    }

    public function testPadLpaIdWhenLpaIdIsTooBig(): void
    {
        $exceptionThrown = false;
        try {
            $paddedId = LpaIdHelper::padLpaId('123456789011');
        } catch (Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }

    public function testConstructPaymentTransactionId(): void
    {
        $id = LpaIdHelper::constructPaymentTransactionId('123');

        $this->assertEquals(
            '00000000123',
            $id
        );

        $this->assertTrue(is_numeric($id));
    }
}
