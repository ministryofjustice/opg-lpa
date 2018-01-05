<?php

namespace OpgTest\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\Common\Dob;

class TestableDob extends Dob
{
    public function testMap($property, $v)
    {
        return parent::map($property, $v);
    }

    public function testDateMap($v)
    {
        return self::testMap('date', $v);
    }
}
