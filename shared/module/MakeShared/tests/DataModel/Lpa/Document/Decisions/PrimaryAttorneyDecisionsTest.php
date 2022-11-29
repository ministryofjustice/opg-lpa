<?php

namespace MakeSharedTest\DataModel\Lpa\Document\Decisions;

use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class PrimaryAttorneyDecisionsTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(PrimaryAttorneyDecisions::class);

        PrimaryAttorneyDecisions::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->properties));
        $this->assertNotNull($metadata->properties['when']);
        $this->assertNotNull($metadata->properties['canSustainLife']);
        $whenMetadata = $metadata->getPropertyMetadata('when');
        $this->assertEquals([
            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
        ], $whenMetadata[0]->constraints[1]->choices);
    }

    public function testValidation()
    {
        $primaryAttorneyDecisions = FixturesData::getPrimaryAttorneyDecisions(FixturesData::getHwLpa());

        $validatorResponse = $primaryAttorneyDecisions->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
        $primaryAttorneyDecisions->set('when', 'incorrect');

        $validatorResponse = $primaryAttorneyDecisions->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['when']);
    }

    public function testGetsAndSets()
    {
        $model = new PrimaryAttorneyDecisions();

        $model->setHow(AbstractDecisions::LPA_DECISION_HOW_DEPENDS)
            ->setWhen(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY)
            ->setHowDetails('details')
            ->setCanSustainLife(true);

        $this->assertEquals(AbstractDecisions::LPA_DECISION_HOW_DEPENDS, $model->getHow());
        $this->assertEquals(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY, $model->getWhen());
        $this->assertEquals('details', $model->getHowDetails());
        $this->assertEquals(true, $model->isCanSustainLife());
    }
}
