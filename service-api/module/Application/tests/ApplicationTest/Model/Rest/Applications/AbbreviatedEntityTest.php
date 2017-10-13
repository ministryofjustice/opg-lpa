<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\AbbreviatedEntity;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class AbbreviatedEntityTest extends TestCase
{
    public function testToArray()
    {
        $lpa = FixturesData::getHwLpa();
        $abbreviatedEntity = new AbbreviatedEntity($lpa);

        $toArray = $abbreviatedEntity->toArray();
        $this->assertEquals(10, count($toArray));
        $this->assertEquals($lpa->get('id'), $toArray['id']);
    }
}