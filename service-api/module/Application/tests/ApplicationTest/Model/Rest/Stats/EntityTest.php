<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\Stats\Entity;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Entity
     */
    private $entity = null;

    /**
     * @var array
     */
    private $stats = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        //  Set up a user to test
        $this->stats = ['stat' => 'test'];
        $this->entity = new Entity($this->stats);
    }

    public function testUserId()
    {
        $this->assertNull($this->entity->userId());
    }

    public function testLpaId()
    {
        $this->assertNull($this->entity->lpaId());
    }

    public function testResourceId()
    {
        $this->assertNull($this->entity->resourceId());
    }

    public function testToArray()
    {
        $this->assertEquals($this->stats, $this->entity->toArray());
    }
}