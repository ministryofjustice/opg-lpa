<?php
namespace Application\Library\ApiProblem;

use Opg\Lpa\DataModel\Validator\ValidatorResponseInterface;

/**
 * Special case API problem for LPA Data Model validation errors.
 *
 * Class ValidationApiProblem
 * @package Application\Library\ApiProblem
 */
class ValidationApiProblem extends ApiProblem {


    public function __construct( ValidatorResponseInterface $response ){

        parent::__construct(
            400,
            'Your request could not be processed due to validation error',
            'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
            'Bad Request'
        );

        $this->additionalDetails = array_merge( $this->additionalDetails, [ 'validation'=>$response->getArrayCopy() ] );

    } // function

} // class
