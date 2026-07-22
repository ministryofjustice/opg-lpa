<?php

namespace Application\Library\Authorization;

use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use RuntimeException;

/**
 * An unauthorized exception that:
 *  - Sets the correct default code
 *  - Implements ApiProblemExceptionInterface so it can be caught and output as an ApiProblem.
 *
 * Class UnauthorizedException
 * @package Application\Library\Authorization
 */
class UnauthorizedException extends RuntimeException implements ApiProblemExceptionInterface
{
    public function __construct($message = "", $code = 401, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
