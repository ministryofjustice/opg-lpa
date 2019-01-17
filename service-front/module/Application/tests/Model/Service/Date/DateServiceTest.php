<?php

namespace ApplicationTest\Model\Service\Date;

use Application\Model\Service\Date\DateService;
use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;

class DateServiceTest extends TestCase
{
    /**
     * @var $dateService DateService
     */
    private $dateService;


    public function setUp() : void
    {
        $this->dateService = new DateService();
    }

    public function testGetNow() : void
    {
        $nowish = new DateTime('now');

        $result = $this->dateService->getNow();

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertGreaterThanOrEqual($nowish, $result);
        $this->assertLessThanOrEqual($nowish->add(new DateInterval('PT1S')), $result);
    }

    public function testGetToday() : void
    {
        $this->assertEquals(new DateTime('today'), $this->dateService->getToday());
    }
}
