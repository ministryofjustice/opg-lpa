<?php

namespace MakeSharedTest\DataModel\Lpa\Document\Decisions;

use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ReplacementAttorneyDecisionsTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(ReplacementAttorneyDecisions::class);

        ReplacementAttorneyDecisions::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->getConstrainedProperties()));
        $this->assertContains('when', $metadata->getConstrainedProperties());
        $this->assertContains('whenDetails', $metadata->getConstrainedProperties());

        $whenMetadata = $metadata->getPropertyMetadata('when');
        $this->assertEquals([
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
        ], $whenMetadata[0]->getConstraints()[1]->choices);
    }

    public function testValidation()
    {
        $replacementAttorneyDecisions = FixturesData::getReplacementAttorneyDecisions(FixturesData::getPfLpa());

        $validatorResponse = $replacementAttorneyDecisions->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
        $replacementAttorneyDecisions->set('when', 'incorrect');

        $validatorResponse = $replacementAttorneyDecisions->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['when']);
    }

    public function testGetsAndSets()
    {
        $model = new ReplacementAttorneyDecisions();

        $model->setHow(AbstractDecisions::LPA_DECISION_HOW_DEPENDS)
            ->setWhen(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS)
            ->setHowDetails('details')
            ->setWhenDetails('when details');

        $this->assertEquals(AbstractDecisions::LPA_DECISION_HOW_DEPENDS, $model->getHow());
        $this->assertEquals(ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS, $model->getWhen());
        $this->assertEquals('details', $model->getHowDetails());
        $this->assertEquals('when details', $model->getWhenDetails());
    }
}
