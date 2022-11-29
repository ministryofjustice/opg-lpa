<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Lpa\Document\Document;

class TestableDocument extends Document
{
    public function testMap($property, $v)
    {
        return parent::map($property, $v);
    }
}
