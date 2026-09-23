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

        $this->assertEquals(2, count($metadata->getConstrainedProperties()));
        $this->assertContains('when', $metadata->getConstrainedProperties());
        $this->assertContains('canSustainLife', $metadata->getConstrainedProperties());

        $whenMetadata = $metadata->getPropertyMetadata('when');
        $this->assertEquals([
            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW,
            PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY
        ], $whenMetadata[0]->getConstraints()[1]->choices);
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

    public function testValidationErrorsExcludeTheEnteredHowDetails()
    {
        $details = 'My social security number is 943 476 5919. '
            . str_repeat('a', (1000 * 1024));

        $decisions = new PrimaryAttorneyDecisions();
        $decisions->setHow(AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
        $decisions->setHowDetails($details);

        $errors = $decisions->validate()->getArrayCopy();

        $this->assertEquals(['messages'], array_keys($errors['howDetails']));
        $this->assertStringNotContainsString('943 476 5919', (string)json_encode($errors));
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
