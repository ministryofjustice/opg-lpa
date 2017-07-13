<?php

namespace ApplicationTest\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\AttorneysPrimary\Collection;
use Application\Model\Rest\AttorneysPrimary\Entity;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
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
        $lpa = FixturesData::getPfLpa();
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
        $lpa = FixturesData::getPfLpa();
        $primaryAttorneys = $lpa->document->primaryAttorneys;
        $collection = new Collection(new ArrayAdapter($primaryAttorneys), $lpa);

        $array = $collection->toArray();

        $this->assertEquals(count($primaryAttorneys), $array['count']);
        $this->assertEquals(count($primaryAttorneys), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        for ($i = 0; $i < count($primaryAttorneys); $i++) {
            $this->assertTrue($array['items'][$i] instanceof Entity);
            $this->assertEquals(new Entity($primaryAttorneys[$i], $lpa), $items[$i]);
        }
    }
}