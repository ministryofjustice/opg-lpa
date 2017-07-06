<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class TestableDocument extends Document
{
    public function testMap($property, $v)
    {
        return parent::map($property, $v);
    }
}
