<?php

namespace ApplicationTest\Model\Service\Lock;

use Application\Model\Service\Lock\Entity;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    /**
     * @var Entity
     */
    private $entity = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        //  Set up an LPA to test
        $lpa = FixturesData::getPfLpa();
        $this->entity = new Entity($lpa);
    }

    public function testToArray()
    {
        $this->assertEquals([
            'locked'   => false,
            'lockedAt' => '2017-03-09T15:26:19.563000+0000'
        ], $this->entity->toArray());
    }

    public function testToArrayNull()
    {
        $lpa = new Lpa();
        $entity = new Entity($lpa);
        $this->assertEquals(array(), $entity->toArray());
    }
}
