<?php

namespace ApplicationTest\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\AttorneysPrimary\Entity;
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
        $this->lpa = FixturesData::getPfLpa();
        $this->entity = new Entity($this->lpa->document->primaryAttorneys[0], $this->lpa);
    }

    public function testUserId()
    {
        $this->assertEquals('3c500ccf2f8b65c67bc388e8872b31fc', $this->entity->userId());
    }

    public function testLpaId()
    {
        $this->assertEquals('91333263035', $this->entity->lpaId());
    }

    public function testResourceId()
    {
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->id, $this->entity->resourceId());
    }

    public function testToArray()
    {
        $this->assertEquals($this->lpa->document->primaryAttorneys[0]->toArray(), $this->entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null, $this->lpa);
        $this->assertEquals(array(), $entity->toArray());
    }
}