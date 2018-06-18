<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Model\Service\Seed\Entity;
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
        $this->entity = new Entity($this->lpa);
    }

    public function testToArray()
    {
        $document = $this->lpa->getDocument()->toArray();
        $expected = [
            'seed' => $this->lpa->getId(),
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
        $entity = new Entity($seedLpa);
        $this->assertEquals(['seed' => $seedLpa->id], $entity->toArray());
    }
}
