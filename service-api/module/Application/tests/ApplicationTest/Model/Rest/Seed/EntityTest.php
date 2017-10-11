<?php

namespace ApplicationTest\Model\Rest\Seed;

use Application\Model\Rest\Seed\Entity;
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
        $this->entity = new Entity($this->lpa, $this->lpa);
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
        $document = $this->lpa->document->toArray();
        $expected = [
            'seed' => $this->lpa->id,
            'donor' => $document['donor'],
            'correspondent' => $document['correspondent'],
            'certificateProvider' => $document['certificateProvider'],
            'primaryAttorneys' => $document['primaryAttorneys'],
            'replacementAttorneys' => $document['replacementAttorneys'],
            'peopleToNotify' => $document['peopleToNotify']
        ];
        $this->assertEquals($expected, $this->entity->toArray());
    }

    public function testToArrayDocumentNull()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->document = null;
        $entity = new Entity($seedLpa, $this->lpa);
        $this->assertEquals(['seed' => $seedLpa->id], $entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null, $this->lpa);
        $this->assertEquals(array(), $entity->toArray());
    }
}