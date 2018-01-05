<?php

namespace Opg\Lpa\DataModel\Validator\Constraints\Custom;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use DateTime;

/**
 * Validates that the passed value is an instance of DateTime with the timezone set to UTC.
 *
 * Class DateTimeUTCValidator
 * @package Opg\Lpa\DataModel\Validator\Constraints
 */
class DateTimeUTCValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        /** @var DateTimeUTC $constraint */
        if (!$value instanceof DateTime) {
            $this->context->buildViolation($constraint->notDateTimeMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        } elseif ($value->getOffset() !== 0) { // i.e. ensure there's no offset from UTC
            $this->context->buildViolation($constraint->notUtcMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
