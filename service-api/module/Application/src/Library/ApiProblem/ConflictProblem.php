<?php

declare(strict_types=1);

namespace Application\Library\ApiProblem;

use Application\Model\DataAccess\Repository\Application\ConflictException;

/**
 * Special case API problem for LPA Data Model validation errors.
 */
class ConflictProblem extends ApiProblem
{
    public function __construct(ConflictException $e)
    {
        parent::__construct(
            412,
            'Your request could not be processed due to a conflict',
            null,
            'Conflict'
        );

        $this->additionalDetails = ['lastUpdatedBy' => $e->lastUpdatedBy];
    }
}
