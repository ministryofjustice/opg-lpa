<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Entity;
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

    private $data = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->data = array(
            'type' => 'lpa120',
            'complete' => true,
            'status' => Entity::STATUS_IN_QUEUE,
        );

        //  Set up an LPA to test
        $this->lpa = FixturesData::getHwLpa();
        $this->entity = new Entity($this->data, $this->lpa);
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
        $this->assertEquals($this->data['type'], $this->entity->resourceId());
    }

    public function testToArray()
    {
        $this->assertEquals($this->data, $this->entity->toArray());
    }
}