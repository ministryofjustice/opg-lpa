<?php

namespace MakeShared\DataModel\Validator\Constraints;

/**
 * This trait should be included in any 'Validator\Constraints' class that wants to use
 * its original Symfony Validator (which should be all non-custom ones!).
 *
 * Class ValidatorPathTrait
 * @package MakeShared\DataModel\Validator\Constraints
 */
trait ValidatorPathTrait
{
    /**
     * Returns the name of the class that validates this constraint.
     *
     * This has been changed to point back to the original Symfony Validators.
     *
     * @api
     */
    public function validatedBy(): string
    {
        $pathParts = explode('\\', get_class($this));
        return 'Symfony\\Component\\Validator\\Constraints\\' . end($pathParts) . 'Validator';
    }
}
