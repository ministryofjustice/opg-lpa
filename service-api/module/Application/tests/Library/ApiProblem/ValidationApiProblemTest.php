<?php

declare(strict_types=1);

namespace ApplicationTest\Library\ApiProblem;

use MakeShared\DataModel\Validator\ValidatorResponse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ValidationApiProblemTest extends MockeryTestCase
{
    public function testConstructor(): void
    {
        $validatorResponse = Mockery::mock(ValidatorResponse::class);
        $validatorResponse->shouldReceive('getArrayCopy')->andReturn(['some error'])->once();

        $validationApiProblem = new TestableValidationApiProblem($validatorResponse);

        $this->assertEquals(
            [
                'validation' => [0 => 'some error'],
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error'
            ],
            $validationApiProblem->toArray()
        );

        $this->assertEquals(['validation' => ['some error']], $validationApiProblem->getAdditionalDetails());
    }
}
