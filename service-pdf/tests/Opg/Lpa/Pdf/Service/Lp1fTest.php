<?php

namespace Opg\Lpa\Pdf\Service;

use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Service\Lp1f;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Lp1fTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Lp1f $lp1f;
     */
    private $lp1f;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->lp1f = new Lp1f(new Lpa);
    }
    
    public function testPopulate()
    {
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}