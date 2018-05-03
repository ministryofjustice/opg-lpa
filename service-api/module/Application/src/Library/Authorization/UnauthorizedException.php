<?php
namespace Application\Library\Authorization;

use Exception;
use ZfcRbac\Exception\UnauthorizedException as ZfcRbacUnauthorizedException;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;

/**
 * An extension of the ZfcRbac exception that:
 *  - Sets the correct default code
 *  - Implements ApiProblemExceptionInterface so it can be caught and output as a ApiProblem.
 *
 * Class UnauthorizedException
 * @package Application\Library\Authorization
 */
class UnauthorizedException extends ZfcRbacUnauthorizedException implements ApiProblemExceptionInterface {

    public function __construct($message = "", $code = 401, Exception $previous = null) {

        parent::__construct( $message, $code, $previous );

    }

} // class
