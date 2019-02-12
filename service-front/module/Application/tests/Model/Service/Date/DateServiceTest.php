<?php

namespace ApplicationTest\Model\Service\Date;

use Application\Model\Service\Date\DateService;
use ApplicationTest\Model\Service\ServiceTestHelper;
use DateTime;
use Exception;
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

    /**
     * @throws Exception
     */
    public function testGetNow() : void
    {
        $result = $this->dateService->getNow();

        $this->assertInstanceOf(DateTime::class, $result);
        ServiceTestHelper::assertTimeNear(new DateTime('now'), $result);
    }

    /**
     * @throws Exception
     */
    public function testGetToday() : void
    {
        $this->assertEquals(new DateTime('today'), $this->dateService->getToday());
    }
}
