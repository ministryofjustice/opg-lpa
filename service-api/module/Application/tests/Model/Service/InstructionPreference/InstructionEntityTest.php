<?php

namespace ApplicationTest\Model\Service\InstructionPreference;

use Application\Model\Service\InstructionPreference\InstructionEntity;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class InstructionEntityTest extends TestCase
{
    /**
     * @var InstructionEntity
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
        $this->entity = new InstructionEntity($this->lpa->getDocument()->getInstruction());
    }

    public function testToArray()
    {
        $this->assertEquals(['instruction' => $this->lpa->getDocument()->getInstruction()], $this->entity->toArray());
    }

    public function testToArrayNull()
    {
        $entity = new InstructionEntity(null);
        $this->assertEquals([], $entity->toArray());
    }
}
