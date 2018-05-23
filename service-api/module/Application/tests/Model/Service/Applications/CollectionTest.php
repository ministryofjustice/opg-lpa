<?php

namespace ApplicationTest\Model\Service\Applications;

use Application\Model\Service\Applications\Collection;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;
use Zend\Paginator\Adapter\ArrayAdapter;

class CollectionTest extends TestCase
{
    public function testToArray()
    {
        $lpa1 = FixturesData::getHwLpa();
        $lpa2 = FixturesData::getPfLpa();
        $lpaArray = [$lpa1, $lpa2];
        $collection = new Collection(new ArrayAdapter($lpaArray));

        $array = $collection->toArray();

        $this->assertEquals(count($lpaArray), $array['total']);
        $this->assertEquals($array['applications'][0], $lpa1->abbreviatedToArray());
        $this->assertEquals($array['applications'][1], $lpa2->abbreviatedToArray());
    }
}
