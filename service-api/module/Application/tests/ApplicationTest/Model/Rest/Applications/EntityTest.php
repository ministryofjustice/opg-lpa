<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
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
        $this->lpa = FixturesData::getPfLpa();
        $this->entity = new Entity($this->lpa);
    }

    public function testUserId()
    {
        $this->assertSame('3c500ccf2f8b65c67bc388e8872b31fc', $this->entity->userId());
    }

    public function testLpaId()
    {
        $this->assertEquals('91333263035', $this->entity->lpaId());
    }

    public function testResourceId()
    {
        $this->assertNull($this->entity->resourceId());
    }

    public function testGetLpa()
    {
        $this->assertSame($this->lpa, $this->entity->getLpa());
    }

    public function testToArray()
    {
        $this->assertSame($this->lpa->toArray(), $this->entity->toArray());
    }
}
