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
        $lpaArray = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $collection = new Collection(new ArrayAdapter($lpaArray), 1);

        $array = $collection->toArray();

        $this->assertEquals(count($lpaArray), $array['count']);
        $this->assertEquals(count($lpaArray), $array['total']);
        $this->assertEquals(1, $array['pages']);
        $this->assertTrue($array['items'][0] instanceof AbbreviatedEntity);
        $this->assertTrue($array['items'][1] instanceof AbbreviatedEntity);
        /* @var $items AbbreviatedEntity[] */
        $items = $array['items'];
        $this->assertEquals($lpaArray[0], $items[0]->getLpa());
        $this->assertEquals($lpaArray[1], $items[1]->getLpa());
    }
}
