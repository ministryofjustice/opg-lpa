<?php

namespace MakeShared\DataModel\Validator\Constraints\Custom;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that the passed array of actors does not contain any duplicate IDs.
 *
 * Class UniqueIdInArrayValidator
 * @package MakeShared\DataModel\Validator\Constraints
 */
class UniqueIdInArrayValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || empty($value)) {
            return;
        }

        $ids = []; // Array of ids we've seen so far.

        foreach ($value as $actor) {
            // Don't includes actors with no id set.
            if (is_null($actor->id)) {
                continue;
            }

            /** @var UniqueIdInArray $constraint */
            if (in_array($actor->id, $ids)) {
                $this->context->buildViolation($constraint->notUnique)
                    ->setInvalidValue("Duplicate value: {$actor->id}")
                    ->addViolation();

                return;
            }

            $ids[] = $actor->id;
        }
    }
}
