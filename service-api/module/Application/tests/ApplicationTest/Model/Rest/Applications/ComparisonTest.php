<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class ComparisonTest extends TestCase
{
    public function testEntityIsEqual()
    {
        $lpaEntity = self::getPfApplicationEntity();
        $comparisonLpaEntity = self::getPfApplicationEntity();

        $this->assertTrue($lpaEntity->equals($comparisonLpaEntity));
    }

    public function testEntityIsNotEqual()
    {
        $lpaEntity = self::getPfApplicationEntity();
        $comparisonLpaEntity = self::getPfApplicationEntity();

        $comparisonLpaEntity->getLpa()->document->donor->name->first = "Edited";

        //Verify edits have been applied
        $this->assertEquals("Ayden", $lpaEntity->getLpa()->document->donor->name->first);
        $this->assertEquals("Edited", $comparisonLpaEntity->getLpa()->document->donor->name->first);

        $this->assertFalse($lpaEntity->equals($comparisonLpaEntity));
    }

    public function testEntityIsNotEqualMetadata()
    {
        $lpaEntity = self::getPfApplicationEntity();
        $comparisonLpaEntity = self::getPfApplicationEntity();

        $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']++;

        //Verify edits have been applied
        $this->assertEquals(4, $lpaEntity->getLpa()->metadata['analyticsReturnCount']);
        $this->assertEquals(5, $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']);

        $this->assertFalse($lpaEntity->equals($comparisonLpaEntity));
    }

    public function testEntityIsEqualIgnoringMetadata()
    {
        $lpaEntity = self::getPfApplicationEntity();
        $comparisonLpaEntity = self::getPfApplicationEntity();

        $comparisonLpaEntity->getLpa()->metadata['analyticsReturnCount']++;

        $this->assertTrue($lpaEntity->equalsIgnoreMetadata($comparisonLpaEntity));
    }

    private function getPfApplicationEntity()
    {
        return new Entity(FixturesData::getPfLpa());
    }
}
