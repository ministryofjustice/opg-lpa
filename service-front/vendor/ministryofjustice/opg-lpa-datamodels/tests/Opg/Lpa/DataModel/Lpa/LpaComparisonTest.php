<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class ComparisonTest extends \PHPUnit_Framework_TestCase
{
    public function testLpaIsEqual()
    {
        $lpa = self::getPfLpa();
        $comparisonLpa = self::getPfLpa();

        //Reference should be different
        $this->assertFalse($lpa === $comparisonLpa);
        //But the object should be structurally the same
        $this->assertTrue($lpa == $comparisonLpa);
        $this->assertEquals($lpa, $comparisonLpa);
        $this->assertTrue($lpa->equals($comparisonLpa));
    }

    public function testLpaIsNotEqual()
    {
        $lpa = self::getPfLpa();
        $comparisonLpa = self::getPfLpa();

        $comparisonLpa->document->donor->name->first = "Edited";

        //Verify edits have been applied
        $this->assertEquals("Ayden", $lpa->document->donor->name->first);
        $this->assertEquals("Edited", $comparisonLpa->document->donor->name->first);

        $this->assertFalse($lpa == $comparisonLpa);
        $this->assertNotEquals($lpa, $comparisonLpa);
        $this->assertFalse($lpa->equals($comparisonLpa));
    }

    public function testLpaIsNotEqualMetadata()
    {
        $lpa = self::getPfLpa();
        $comparisonLpa = self::getPfLpa();

        $comparisonLpa->metadata['analyticsReturnCount']++;

        //Verify edits have been applied
        $this->assertEquals(4, $lpa->metadata['analyticsReturnCount']);
        $this->assertEquals(5, $comparisonLpa->metadata['analyticsReturnCount']);

        $this->assertFalse($lpa == $comparisonLpa);
        $this->assertNotEquals($lpa, $comparisonLpa);
        $this->assertFalse($lpa->equals($comparisonLpa));
    }

    public function testLpaIsEqualIgnoringMetadata()
    {
        $lpa = self::getPfLpa();
        $comparisonLpa = self::getPfLpa();

        $comparisonLpa->metadata['analyticsReturnCount']++;

        $this->assertTrue($lpa->document == $comparisonLpa->document);
        $this->assertEquals($lpa->document, $comparisonLpa->document);
        $this->assertTrue($lpa->equalsIgnoreMetadata($comparisonLpa));
    }

    private function getPfLpa()
    {
        return new Lpa(file_get_contents(__DIR__ . '/../../../../fixtures/pf.json'));
    }
}
