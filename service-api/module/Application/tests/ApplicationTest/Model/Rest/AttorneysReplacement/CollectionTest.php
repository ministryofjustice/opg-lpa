<?php

namespace ApplicationTest\Model\Rest\AttorneysReplacement;

use Application\Model\Rest\AttorneysReplacement\Collection;
use Application\Model\Rest\AttorneysReplacement\Entity;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Adapter\NullFill;

class CollectionTest extends TestCase
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
        $replacementAttorneys = $lpa->document->replacementAttorneys;
        $collection = new Collection(new ArrayAdapter($replacementAttorneys), $lpa);

        $array = $collection->toArray();

        $this->assertEquals(count($replacementAttorneys), $array['count']);
        $this->assertEquals(count($replacementAttorneys), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        for ($i = 0; $i < count($replacementAttorneys); $i++) {
            $this->assertTrue($array['items'][$i] instanceof Entity);
            $this->assertEquals(new Entity($replacementAttorneys[$i], $lpa), $items[$i]);
        }
    }
}