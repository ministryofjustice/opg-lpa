<?php

namespace Application\Model\DataAccess\Repository\Application;

use Application\Library\ApiProblem\ApiProblemExceptionInterface;

class ConflictException extends \RuntimeException implements ApiProblemExceptionInterface
{
    public function __construct(string $lastUpdatedBy)
    {
        parent::__construct(json_encode([
            'lastUpdatedBy' => $lastUpdatedBy,
        ]), 412, null);
    }
}
