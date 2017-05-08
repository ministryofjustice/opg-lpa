<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
use Opg\Lpa\DataModel\Lpa\Lpa;

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
        $this->lpa = new Lpa(file_get_contents(__DIR__ . '/../../../fixtures/pf.json'));
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

    public function getLpa()
    {
        $this->assertSame($this->lpa, $this->entity->getLpa());
    }

    public function toArray()
    {
        $this->assertSame($this->lpa->toArray(), $this->entity->toArray());
    }
}
