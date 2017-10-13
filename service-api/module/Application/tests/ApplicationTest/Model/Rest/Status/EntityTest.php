<?php

namespace ApplicationTest\Model\Rest\Status;

use Application\Model\Rest\Status\Entity;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
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

        $state = new StateChecker($this->lpa);

        $status = [
            'started' => $state->isStateStarted(),
            'created' => $state->isStateCreated(),
            'completed' => $state->isStateCompleted()
        ];

        $this->entity = new Entity($status, $this->lpa);
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
        $status = [
            'started' => true,
            'created' => true,
            'completed' => true,
        ];

        $this->assertEquals($status, $this->entity->toArray());
    }
}