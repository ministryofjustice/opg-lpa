<?php

namespace MakeSharedTest\DataModel\Lpa\Document\Decisions;

use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AbstractDecisionsTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(AbstractDecisions::class);

        AbstractDecisions::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->getConstrainedProperties()));
        $this->assertContains('how', $metadata->getConstrainedProperties());
        $this->assertContains('howDetails', $metadata->getConstrainedProperties());

        $howMetadata = $metadata->getPropertyMetadata('how');
        $this->assertEquals([
            AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY,
            AbstractDecisions::LPA_DECISION_HOW_SINGLE_ATTORNEY,
            AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        ], $howMetadata[0]->getConstraints()[1]->choices);
    }
}
