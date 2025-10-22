<?php

namespace Application\Library\Authorization;

use Exception;
use LmcRbacMvc\Exception\UnauthorizedException as LmcRbacUnauthorizedException;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;

/**
 * An extension of the LmcRbac exception that:
 *  - Sets the correct default code
 *  - Implements ApiProblemExceptionInterface so it can be caught and output as a ApiProblem.
 *
 * Class UnauthorizedException
 * @package Application\Library\Authorization
 */
class UnauthorizedException extends LmcRbacUnauthorizedException implements ApiProblemExceptionInterface
{
    public function __construct($message = "", $code = 401, Exception|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
