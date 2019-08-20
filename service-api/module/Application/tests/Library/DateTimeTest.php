<?php

namespace ApplicationTest\Library;

use Application\Library\DateTime;
use PHPUnit\Framework\TestCase;

class DateTimeTest extends TestCase {

    public function testDateTimeDisplaysMicroseconds() {
        $dateTime = new DateTime('2018-10-01 10:06:36.722455 UTC');

        $this->assertEquals('2018-10-01T10:06:36.722455+0000', $dateTime->format('Y-m-d\TH:i:s.uO'));
    }

}
