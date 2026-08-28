<?php

namespace Application\Model\DataAccess\Repository\Application;

use Application\Library\ApiProblem\ApiProblemExceptionInterface;

/**
 * Thrown if a an amend is attempted on a locked LPA.
 *
 * Class LockedException
 * @package Application\Model\DataAccess\Repository\Application
 */
class LockedException extends \RuntimeException implements ApiProblemExceptionInterface
{
    public function __construct(string $message = "", int $code = 403, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
