<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Service\Forms\Form;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
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
        
        $lpa = new Lpa(file_get_contents(__DIR__.'/../../../../../test-data/test-1.json'));
        
        $this->lp1f = new Lp1f($lpa);
    }
    
    public function testPopulate()
    {
        $this->assertTrue($this->lp1f instanceof Lp1f);
        
        $this->assertObjectHasAttribute('lpa', $this->lp1f);
        $this->assertObjectHasAttribute('pdf', $this->lp1f);
        $this->assertObjectHasAttribute('flattenLpa', $this->lp1f);
        
        $this->assertTrue($this->lp1f->generate() instanceof Lp1f);
        $this->assertTrue($this->lp1f->getPdfObject() instanceof Pdf);
        
        $pdfFormFieldsData = $this->lp1f->getPdfObject()->getDataFields();
        
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}