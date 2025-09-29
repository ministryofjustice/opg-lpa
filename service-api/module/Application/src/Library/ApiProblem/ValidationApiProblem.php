<?php

declare(strict_types=1);

namespace Application\Library\ApiProblem;

use MakeShared\DataModel\Validator\ValidatorResponseInterface;

/**
 * Special case API problem for LPA Data Model validation errors.
 */
class ValidationApiProblem extends ApiProblem
{
    public function __construct(ValidatorResponseInterface $response)
    {
        parent::__construct(
            400,
            'Your request could not be processed due to validation error',
            'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
            'Bad Request'
        );

        $validationResult = ['validation' => $response->getArrayCopy()];

        $this->additionalDetails = array_merge($validationResult, $this->additionalDetails);
    }
}
