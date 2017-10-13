<?php

namespace ApplicationTest\Model\Rest\Users;

use Application\Model\Rest\Users\Entity;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    /**
     * @var Entity
     */
    private $entity = null;

    /**
     * @var User
     */
    private $user = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        //  Set up a user to test
        $this->user = FixturesData::getUser();
        $this->entity = new Entity($this->user);
    }

    public function testUserId()
    {
        $this->assertEquals($this->user->id, $this->entity->userId());
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
        $this->assertEquals($this->user->toArray(), $this->entity->toArray());
    }
}