<?php

namespace OpgTest\Lpa\DataModel\User;

use Opg\Lpa\DataModel\Common\Dob;

class TestableDob extends Dob
{
    public function testDateMap($v)
    {
        return self::map('date', $v);
    }
}
