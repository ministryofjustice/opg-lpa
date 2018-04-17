<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\AbbreviatedEntity;
use Application\Model\Rest\Applications\Collection;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Adapter\NullFill;

class CollectionTest extends TestCase
{
    public function testConstructor()
    {
        $userId = 27;
        $collection = new Collection(new NullFill(), $userId);
        $this->assertEquals($userId, $collection->userId());

        //Both are always null so test here
        $this->assertNull($collection->lpaId());
        $this->assertNull($collection->resourceId());
    }

    public function testToArray()
    {
        $lpa1 = FixturesData::getHwLpa();
        $lpa2 = FixturesData::getPfLpa();
        $lpaArray = [$lpa1, $lpa2];
        $collection = new Collection(new ArrayAdapter($lpaArray), 1);

        $array = $collection->toArray();

        $this->assertEquals(count($lpaArray), $array['total']);
        $this->assertEquals($array['applications'][0], (new AbbreviatedEntity($lpa1))->toArray());
        $this->assertEquals($array['applications'][1], (new AbbreviatedEntity($lpa2))->toArray());
    }
}
