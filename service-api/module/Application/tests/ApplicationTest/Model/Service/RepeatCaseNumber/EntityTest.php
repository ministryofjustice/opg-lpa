<?php

namespace ApplicationTest\Model\Service\RepeatCaseNumber;

use Application\Model\Service\RepeatCaseNumber\Entity;
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
        $this->entity = new Entity($this->lpa->repeatCaseNumber);
    }

    public function testToArray()
    {
        $entity = new Entity('123456789');
        $this->assertEquals(['repeatCaseNumber' => '123456789'], $entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null);
        $this->assertEquals(array(), $entity->toArray());
    }
}