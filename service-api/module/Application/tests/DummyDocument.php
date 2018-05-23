<?php

namespace ApplicationTest;

use InvalidArgumentException;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class DummyDocument extends Document
{
    public function setDirect($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new InvalidArgumentException("{$property} is not a valid property");
        }

        $this->{$property} = $value;
    }
}