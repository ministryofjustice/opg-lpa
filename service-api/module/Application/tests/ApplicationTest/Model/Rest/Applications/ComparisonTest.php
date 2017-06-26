<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
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
    }

    public function testEntityIsEqual()
    {
        $lpaEntity = self::getPfLpaEntity();
        $comparisonLpaEntity = self::getPfLpaEntity();

        $this->assertTrue($lpaEntity->equals($comparisonLpaEntity));
    }

    public function testEntityIsNotEqual()
    {
        $lpaEntity = self::getPfLpaEntity();
        $comparisonLpaEntity = self::getPfLpaEntity();
        $comparisonLpaEntity->getLpa()->document->donor->name->first = "Edited";

        //Verify edits have been applied
        $this->assertEquals("Ayden", $lpaEntity->getLpa()->document->donor->name->first);
        $this->assertEquals("Edited", $comparisonLpaEntity->getLpa()->document->donor->name->first);

        $this->assertFalse($lpaEntity->equals($comparisonLpaEntity));
    }

    public function testEntityIsEqualIgnoringMetadata()
    {
        $lpaEntity = self::getPfLpaEntity();
        $comparisonLpaEntity = self::getPfLpaEntity();

        $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']++;

        $this->assertTrue($lpaEntity->equalsIgnoreMetadata($comparisonLpaEntity));
    }

    private function getPfLpa()
    {
        return new Lpa(file_get_contents(__DIR__ . '/../../../fixtures/pf.json'));
    }

    private function getPfLpaEntity()
    {
        return new Entity(self::getPfLpa());
    }
}
