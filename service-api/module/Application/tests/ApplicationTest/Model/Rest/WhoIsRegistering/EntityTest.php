<?php

namespace ApplicationTest\Model\Rest\WhoIsRegistering;

use Application\Model\Rest\WhoIsRegistering\Entity;
use Mockery;
use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;

class EntityTest extends \PHPUnit_Framework_TestCase
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
        $this->entity = new Entity($this->lpa->document->whoIsRegistering, $this->lpa);
    }

    public function testUserId()
    {
        $this->assertEquals('3c500ccf2f8b65c67bc388e8872b31fc', $this->entity->userId());
    }

    public function testLpaId()
    {
        $this->assertEquals('5531003156', $this->entity->lpaId());
    }

    public function testResourceId()
    {
        $this->assertNull($this->entity->resourceId());
    }

    public function testToArray()
    {
        $this->assertEquals(['who' => $this->lpa->document->whoIsRegistering], $this->entity->toArray());
    }

    public function testToArrayString()
    {
        $entity = new Entity($this->lpa->document->whoIsRegistering[0], $this->lpa);
        $this->assertEquals(['who' => $this->lpa->document->whoIsRegistering[0]], $entity->toArray());
    }

    public function testToArrayAccessorInterface()
    {
        $whoAccessorInterface = Mockery::mock(AbstractData::class);
        $whoAccessorInterface->shouldReceive('toArray')->andReturn('donor');
        $entity = new Entity([$whoAccessorInterface], $this->lpa);
        $this->assertEquals(['who' => ['donor']], $entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null, $this->lpa);
        $this->assertEquals(array(), $entity->toArray());
    }
}