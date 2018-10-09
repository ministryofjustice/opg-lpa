<?php

namespace ApplicationTest\Library\ApiProblem;

use Application\Library\ApiProblem\ApiProblem;
use PHPUnit\Framework\TestCase;

class ApiProblemTest extends TestCase
{
    /**
     * @var ApiProblem
     */
    private $apiProblem;

    public function setUp() : void
    {
        $this->apiProblem = new ApiProblem(200, 'Detail', 'Type', 'Title');
    }

    public function testGetTitle() : void
    {
        $this->assertEquals('Title', $this->apiProblem->getTitle());
    }

    public function testGetStatus()
    {
        $this->assertEquals(200, $this->apiProblem->getStatus());
    }

    public function testGetDetail() {
        $this->assertEquals('Detail', $this->apiProblem->getDetail());
    }

    public function testGetType() {
        $this->assertEquals('Type', $this->apiProblem->getType());

    }
}