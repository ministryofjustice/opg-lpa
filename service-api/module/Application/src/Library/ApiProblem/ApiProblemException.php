<?php
namespace Application\Library\ApiProblem;

use Exception;

/**
 * An exception that if thrown is caught and rendered as a standard ApiProblem.
 * i.e. it does not include a call stack.
 *
 * Class ApiProblemException
 * @package Application\Library\ApiProblem
 */
class ApiProblemException extends Exception implements ApiProblemExceptionInterface {

    public function __construct($message = "", $code = 500, Exception $previous = null) {

        parent::__construct( $message, $code, $previous );

    }

} // class
