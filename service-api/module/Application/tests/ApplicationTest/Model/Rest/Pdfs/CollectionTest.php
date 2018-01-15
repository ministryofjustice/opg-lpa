<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Collection;
use Application\Model\Rest\Pdfs\Entity;
use Application\Model\Rest\Pdfs\Resource;
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

        $resource = (new ResourceBuilder())->build();
        $data = array();
        foreach ($resource->getPdfTypes() as $type) {
            $data[$type] = array(
                'type' => $type,
                'complete' => true,
                'status' => Entity::STATUS_NOT_QUEUED,
            );
        }

        $collection = new Collection(new ArrayAdapter($data), $lpa);

        $array = $collection->toArray();

        $this->assertEquals(count($data), $array['count']);
        $this->assertEquals(count($data), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        foreach ($data as $datum) {
            $this->assertTrue($items[$datum['type']] instanceof Entity);
            $this->assertEquals(new Entity($datum, $lpa), $items[$datum['type']]);
        }
    }
}