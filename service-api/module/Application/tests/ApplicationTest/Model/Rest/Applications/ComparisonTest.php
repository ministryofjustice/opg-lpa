<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ComparisonTest extends \PHPUnit_Framework_TestCase
{
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

    public function testEntityIsNotEqualMetadata()
    {
        $lpaEntity = self::getPfLpaEntity();
        $comparisonLpaEntity = self::getPfLpaEntity();

        $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']++;

        //Verify edits have been applied
        $this->assertEquals(4, $lpaEntity->getLpa()->metadata['analyticsReturnCount']);
        $this->assertEquals(5, $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']);

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
