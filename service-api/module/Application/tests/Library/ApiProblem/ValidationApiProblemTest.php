<?php

namespace ApplicationTest\Library\ApiProblem;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Validator\ValidatorResponseInterface;

class ValidationApiProblemTest extends MockeryTestCase
{
    public function testConstructor() : void
    {
        $validatorResponse = Mockery::mock(ValidatorResponseInterface::class);
        $validatorResponse->shouldReceive('getArrayCopy')->andReturn(['some error'])->once();

        $validationApiProblem = new TestableValidationApiProblem($validatorResponse);

        $this->assertEquals(400, $validationApiProblem->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationApiProblem->getDetail());
        $this->assertEquals('Bad Request', $validationApiProblem->getTitle());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationApiProblem->getType());
        $this->assertEquals(['validation' => ['some error']], $validationApiProblem->getAdditionalDetails());
    }
}
