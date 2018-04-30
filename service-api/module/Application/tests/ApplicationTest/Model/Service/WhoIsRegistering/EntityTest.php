<?php

namespace ApplicationTest\Model\Service\WhoIsRegistering;

use Application\Model\Service\WhoIsRegistering\Entity;
use Mockery;
use Opg\Lpa\DataModel\AbstractData;
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
     * @var Lpa
     */
    private $lpa = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        //  Set up an LPA to test
        $this->lpa = FixturesData::getHwLpa();
        $this->entity = new Entity($this->lpa->document->whoIsRegistering);
    }

    public function testToArray()
    {
        $this->assertEquals(['who' => $this->lpa->document->whoIsRegistering], $this->entity->toArray());
    }

    public function testToArrayString()
    {
        $entity = new Entity($this->lpa->document->whoIsRegistering[0]);
        $this->assertEquals(['who' => $this->lpa->document->whoIsRegistering[0]], $entity->toArray());
    }

    public function testToArrayAccessorInterface()
    {
        $whoAccessorInterface = Mockery::mock(AbstractData::class);
        $whoAccessorInterface->shouldReceive('toArray')->andReturn('donor');
        $entity = new Entity([$whoAccessorInterface]);
        $this->assertEquals(['who' => ['donor']], $entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null);
        $this->assertEquals(array(), $entity->toArray());
    }
}