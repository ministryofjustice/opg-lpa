<?php

declare(strict_types=1);

namespace AppTest\Service\Date;

use App\Service\Date\DateService;
use DateTime;
use PHPUnit\Framework\TestCase;

class DateServiceTest extends TestCase
{
    public function testGetNowReturnsDateTime(): void
    {
        $this->assertInstanceOf(DateTime::class, (new DateService())->getNow());
    }

    public function testGetTodayReturnsDateTime(): void
    {
        $this->assertInstanceOf(DateTime::class, (new DateService())->getToday());
    }
}
