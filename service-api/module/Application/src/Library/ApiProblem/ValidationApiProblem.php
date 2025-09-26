<?php

namespace Application\Library\ApiProblem;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use MakeShared\DataModel\Validator\ValidatorResponseInterface;

/**
 * Special case API problem for LPA Data Model validation errors.
 *
 * Class ValidationApiProblem
 * @package Application\Library\ApiProblem
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

        if (!is_array($this->additionalDetails)) {
            $this->additionalDetails = $validationResult;
        } else {
            $this->additionalDetails = array_merge($validationResult, $this->additionalDetails);
        }
    }
}
