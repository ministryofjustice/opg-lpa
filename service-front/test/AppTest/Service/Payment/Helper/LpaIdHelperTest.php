<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\Helper;

use App\Service\Payment\Helper\LpaIdHelper;
use Exception;
use PHPUnit\Framework\TestCase;

final class LpaIdHelperTest extends TestCase
{
    public function testPadLpaIdPadsShortIdWithLeadingZeros(): void
    {
        $this->assertSame('00000012345', LpaIdHelper::padLpaId('12345'));
    }

    public function testPadLpaIdReturnsExactLengthIdUnchanged(): void
    {
        $this->assertSame('12345678901', LpaIdHelper::padLpaId('12345678901'));
    }

    public function testPadLpaIdThrowsExceptionWhenIdIsTooLong(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA ID is too long');

        LpaIdHelper::padLpaId('123456789012');
    }

    public function testConstructPaymentTransactionIdDelegatesToPadLpaId(): void
    {
        $this->assertSame('00000012345', LpaIdHelper::constructPaymentTransactionId('12345'));
    }
}
