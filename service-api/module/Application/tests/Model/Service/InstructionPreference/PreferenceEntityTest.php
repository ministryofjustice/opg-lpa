<?php

namespace ApplicationTest\Model\Service\InstructionPreference;

use Application\Model\Service\InstructionPreference\PreferenceEntity;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class PreferenceEntityTest extends TestCase
{
    /**
     * @var PreferenceEntity
     */
    private $entity = null;

    /**
     * @var Lpa
     */
    private $lpa = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        //  Set up an LPA to test
        $this->lpa = FixturesData::getHwLpa();
        $this->entity = new PreferenceEntity($this->lpa->getDocument()->getPreference());
    }

    public function testToArray()
    {
        $this->assertEquals(['preference' => $this->lpa->getDocument()->getPreference()], $this->entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new PreferenceEntity(null);
        $this->assertEquals([], $entity->toArray());
    }
}
