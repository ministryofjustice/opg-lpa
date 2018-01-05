<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ReplacementAttorneyDecisionsTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(ReplacementAttorneyDecisions::class);

        ReplacementAttorneyDecisions::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->properties));
        $this->assertNotNull($metadata->properties['when']);
        $this->assertNotNull($metadata->properties['whenDetails']);
        $whenMetadata = $metadata->getPropertyMetadata('when');
        $this->assertEquals([
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS
        ], $whenMetadata[0]->constraints[1]->choices);
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
