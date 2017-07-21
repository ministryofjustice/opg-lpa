<?php

namespace ApplicationTest\Model\Rest\NotifiedPeople;

use Application\Model\Rest\NotifiedPeople\Collection;
use Application\Model\Rest\NotifiedPeople\Entity;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Adapter\NullFill;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $lpa = FixturesData::getHwLpa();
        $collection = new Collection(new NullFill(), $lpa);

        //Always null so test here
        $this->assertNull($collection->resourceId());
    }

    public function testUserId()
    {
        $lpa = FixturesData::getHwLpa();
        $collection = new Collection(new NullFill(), $lpa);

        $this->assertEquals($lpa->user, $collection->userId());
    }

    public function testLpaId()
    {
        $lpa = FixturesData::getHwLpa();
        $collection = new Collection(new NullFill(), $lpa);

        $this->assertEquals($lpa->id, $collection->lpaId());
    }

    public function testToArray()
    {
        $lpa = FixturesData::getHwLpa();
        $peopleToNotify = $lpa->document->peopleToNotify;
        $collection = new Collection(new ArrayAdapter($peopleToNotify), $lpa);

        $array = $collection->toArray();

        $this->assertEquals(count($peopleToNotify), $array['count']);
        $this->assertEquals(count($peopleToNotify), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        for ($i = 0; $i < count($peopleToNotify); $i++) {
            $this->assertTrue($array['items'][$i] instanceof Entity);
            $this->assertEquals(new Entity($peopleToNotify[$i], $lpa), $items[$i]);
        }
    }
}