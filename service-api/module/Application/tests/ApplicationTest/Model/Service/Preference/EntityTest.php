<?php

namespace ApplicationTest\Model\Service\Preference;

use Application\Model\Service\Preference\Entity;
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
        $this->entity = new Entity($this->lpa->document->preference);
    }

    public function testToArray()
    {
        $this->assertEquals(['preference' => $this->lpa->document->preference], $this->entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new Entity(null);
        $this->assertEquals(array(), $entity->toArray());
    }
}