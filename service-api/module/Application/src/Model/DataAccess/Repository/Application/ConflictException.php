<?php

namespace Application\Model\DataAccess\Repository\Application;

use Application\Library\ApiProblem\ApiProblemExceptionInterface;

class ConflictException extends \RuntimeException implements ApiProblemExceptionInterface
{
    public function __construct(public readonly string $lastUpdatedBy)
    {
        parent::__construct('Conflict');
    }
}
