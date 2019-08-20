<?php
namespace Application\Library\ApiProblem;

/**
 * Classes that implement this interface will be caught and returned as a APIProblem.
 *
 * Interface ApiProblemExceptionInterface
 * @package Application\Library\ApiProblem
 */
interface ApiProblemExceptionInterface {

    public function getCode();
    public function getMessage();

}
